<?php

declare(strict_types=1);

namespace Operations;

use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

final readonly class ProgramReflector
{
    private ReflectionClass $class;

    public function __construct(private object $object, private CurrentDirectory $currentDir)
    {
        $this->class = new ReflectionClass($this->object);
    }

    public function getCommands(): iterable
    {
        $order = 0;
        foreach ($this->class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(Command::class);
            if (count($attrs) !== 1) {
                continue;
            }
            $command = $attrs[0]->newInstance();
            assert($command instanceof Command);
            $command->setName($method->name);
            $command->setOrder($order++);

            $parameters = $method->getParameters();
            $countParameters = count($parameters);

            if ($countParameters > 1) {
                throw new LogicException("A command method should not have more than one parameter.");
            }

            if ($countParameters === 0) {
                $command->setArgument(Command::NO_ARGUMENT);
                yield $command;
                continue;
            }

            [$parameter] = $parameters;
            $command->setArgument($parameter->isDefaultValueAvailable() ? Command::OPTIONAL_ARGUMENT : Command::REQUIRED_ARGUMENT);
            yield $command;
        }
    }

    public function getOptions(): iterable
    {
        foreach ($this->class->getProperties() as $property) {
            $attrs = $property->getAttributes(Option::class);
            if (count($attrs) !== 1) {
                continue;
            }
            $option = $attrs[0]->newInstance();
            assert($option instanceof Option);
            $option->setName($property->name);
            $type = $property->getType();
            assert($type instanceof ReflectionNamedType);
            $typeName = $type->getName();
            $option->setType($typeName);
            if ($typeName === CurrentDirectory::class) {
                $property->setValue($this->object, $this->currentDir);
            }
            $option->setFlag($type->getName() === "bool" && $property->hasDefaultValue() && $property->getDefaultValue() === false);
            if ($property->hasDefaultValue()) {
                $option->setDefault($property->getDefaultValue());
            }
            yield $option;
        }
    }

    /**
     * @throws ReflectionException
     */
    public function setOptionValue(Option $option, mixed $value): void
    {
        $property = $this->class->getProperty($option->name);
        switch ($option->type) {
            case "bool":
                if ($option->flag) {
                    $property->setValue($this->object, true);
                    break;
                }
                $property->setValue($this->object, match ($value) {
                    "true", "1" => true,
                    "false", "0" => false,
                    default => throw new RuntimeException("invalid value for $property->name"),
                });
                break;

            case "int":
                if (preg_match('/^0|[1-9][0-9]*$/', $value) !== 1) {
                    throw new RuntimeException("invalid value for $property->name");
                }
                $property->setValue($this->object, (int)$value);
                break;

            case "float":
                if (preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/', $value) !== 1) {
                    throw new RuntimeException("invalid value for $property->name");
                }
                $property->setValue($this->object, (float)$value);
                break;

            case "string":
                $property->setValue($this->object, $this->validate($option, $value));
                break;

            case "array":
                if (is_string($value)) {
                    $value = explode(",", $value);
                }
                foreach ($value as $v) {
                    $this->validate($option, $v);
                }
                $property->setValue($this->object, $value);
                break;

            case CurrentDirectory::class:
                $this->currentDir->set($value);
                break;
        }
    }

    /**
     * @throws ReflectionException
     */
    public function invoke(Command $command, mixed $value): void
    {
        if ($command->argument === Command::NO_ARGUMENT) {
            $this->class->getMethod($command->name)->invoke($this->object);
        } else {
            $this->class->getMethod($command->name)->invoke($this->object, $value);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function validate(Option $option, string $value): string
    {
        if (isset($option->values)) {
            $values = match (true) {
                is_array($option->values) => $option->values,
                str_starts_with($option->values, "%option:") => $this->class->getProperty(substr($option->values, 8))->getValue($this->object),
                str_starts_with($option->values, "%method:") => $this->class->getMethod(substr($option->values, 8))->invoke($this->object),
                default => explode(",", $option->values),
            };
            if (!in_array($value, $values, true)) {
                throw new RuntimeException("Invalid value '$value' for $option->name, available values '" . implode("', '", $option->values) . "'");
            }
        }

        if (isset($option->pattern)) {
            $pattern = match (true) {
                str_starts_with($option->pattern, "%option:") => $this->class->getProperty(substr($option->pattern, 8))->getValue($this->object),
                str_starts_with($option->pattern, "%method:") => $this->class->getMethod(substr($option->pattern, 8))->invoke($this->object),
                default => $option->pattern,
            };
            if (preg_match($pattern, $value) !== 1) {
                throw new RuntimeException("Invalid value '$value' for $option->name, must match pattern $option->pattern");
            }
        }

        if (isset($option->filePattern)) {
            $filePattern = match (true) {
                str_starts_with($option->filePattern, "%option:") => $this->class->getProperty(substr($option->filePattern, 8))->getValue($this->object),
                str_starts_with($option->filePattern, "%method:") => $this->class->getMethod(substr($option->filePattern, 8))->invoke($this->object),
                default => $option->filePattern,
            };
            $file = str_replace("%", $value, $filePattern);
            if (!str_starts_with($file, "/")) {
                $file = "$this->currentDir/$file";
            }
            if (!file_exists($file)) {
                throw new RuntimeException("Invalid value '$value' for $option->name, file $file does not exists.");
            }
            if (!is_file($file)) {
                throw new RuntimeException("Invalid value '$value' for $option->name, file $file is not a file.");
            }
            if (!is_readable($file)) {
                throw new RuntimeException("Invalid value '$value' for $option->name, file $file is not readable.");
            }
        }

        return $value;
    }
}
