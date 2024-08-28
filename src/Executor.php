<?php

declare(strict_types=1);

namespace Operations;

use ReflectionException;
use Throwable;

final readonly class Executor
{
    private ProgramReflector $program;
    private OptionParser $optionParser;
    private EnvParser $envParser;

    /** @var array<string,Command> */
    private array $commands;

    /** @var array<string,Option> */
    private array $options;

    public function __construct(object $object)
    {
        $currentDir = new CurrentDirectory();
        $this->program = new ProgramReflector($object, $currentDir);
        $this->optionParser = new OptionParser();
        $this->envParser = new EnvParser($currentDir);
    }

    /**
     * @throws ReflectionException
     */
    public function execute(): void
    {
        $this->commands = $this->getCommands();
        $this->options = $this->getOptions();
        $this->parseEnv();

        try {
            $methods = $this->parseOptions();
        } catch (Throwable $throwable) {
            $this->help($throwable);
            return;
        }

        if (count($methods) === 0) {
            $this->help();
            return;
        }

        foreach ($methods as $method) {
            if ($method === "help") {
                $this->help();
                continue;
            }
            $this->program->invoke($method);
        }
    }

    private function getCommands(): array
    {
        $commands = [];

        foreach ($this->program->getCommands() as $command) {
            $this->optionParser->addOption($command->short, $command->name, $command, true);
            $commands[$command->name] = $command;
        }

        # Add help command
        $help = new Command("h", "print this help screen", "--help");
        $help->setName("help");
        $help->setOrder(count($commands));
        $this->optionParser->addOption($help->short, $help->name, $help, true);
        $commands[$help->name] = $help;

        return $commands;
    }

    private function getOptions(): array
    {
        $options = [];

        foreach ($this->program->getOptions() as $option) {
            $this->optionParser->addOption($option->short, $option->name, $option, $option->flag);
            $options[$option->name] = $option;
        }

        return $options;
    }

    /**
     * @throws ReflectionException
     */
    private function parseOptions(): array
    {
        $methods = [];

        foreach ($this->optionParser->parse() as [$commandOrOption, $value]) {
            if ($commandOrOption instanceof Option) {
                $this->program->setOptionValue($commandOrOption, $value);
                continue;
            }
            if ($commandOrOption instanceof Command) {
                $methods[$commandOrOption->order] = $commandOrOption->name;
            }
        }

        ksort($methods, SORT_NUMERIC);

        return $methods;
    }

    private function envIterator(): iterable
    {
        yield from $this->envParser->parse();
        yield from getenv();
    }

    /**
     * Get default option values from .env files and env.
     *
     * @throws ReflectionException
     */
    private function parseEnv(): void
    {
        ##
        # Env arrays are env keys with the same prefix.
        #
        # Example:
        #
        #     CMD_ANALYZE="phstan analyze ."
        #     CMD_TEST="phpunit ./tests"
        #
        # Becomes:
        #
        #     $cmd = [
        #         "analyze"=>"phstan analyze .",
        #         "test"=>"phpunit ./tests",
        #     ];
        #

        $envValues = [];
        $envArrayPrefixes = [];
        $envArrayValues = [];

        foreach ($this->options as $option) {
            if (is_string($option->env)) {
                $envArrayPrefixes[] = $option->env;
            }
        }

        foreach ($this->envIterator() as $key => $value) {
            $name = Naming::camelCase($key);
            if (isset($this->options[$name]) && $this->options[$name]->env === true) {
                $envValues[$name] = $value;
                continue;
            }
            foreach ($envArrayPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $subKey = substr($key, strlen($prefix));
                    $subKey = Naming::camelCase($subKey);
                    $envArrayValues[$prefix][$subKey] = $value;
                    continue 2;
                }
            }
        }

        foreach ($this->options as $option) {
            if (is_string($option->env) && isset($envArrayValues[$option->env])) {
                $this->program->setOptionValue($option, $envArrayValues[$option->env]);
            } else if ($option->env === true && isset($envValues[$option->name])) {
                $this->program->setOptionValue($option, $envValues[$option->name]);
            }
        }
    }

    private function help(Throwable|null $throwable = null): void
    {
        $usages = [];
        $commands = [];
        $options = [];
        $envs = [];
        $maxLengthDesc = 0;

        foreach ($this->commands as $command) {
            $usages[] = $command->usage;
            $desc = "  -$command->short,--$command->name";
            $length = strlen($desc);
            if ($length > $maxLengthDesc) {
                $maxLengthDesc = $length;
            }
            $commands[] = [$desc, $command->help ?? ""];
        }

        foreach ($this->options as $option) {
            $desc = match (true) {
                $option->short !== null => "  -$option->short,--$option->name",
                default => "  --$option->name",
            };
            if (!$option->flag) {
                $desc .= " " . Naming::screamingSnakeCase($option->name);
            }
            $length = strlen($desc);
            if ($length > $maxLengthDesc) {
                $maxLengthDesc = $length;
            }
            $options[] = [$desc, $option->help ?? ""];

            if (!$option->env) {
                continue;
            }
            $desc = "  " . Naming::screamingSnakeCase($option->name);
            $length = strlen($desc);
            if ($length > $maxLengthDesc) {
                $maxLengthDesc = $length;
            }
            $envs[] = [$desc, $option->help ?? ""];
        }

        $maxLengthDesc += 3;

        if ($throwable !== null) {
            echo "Error: {$throwable->getMessage()}\n## {$throwable->getFile()}({$throwable->getLine()})\n{$throwable->getTraceAsString()}\n";
        }
        $script = basename($_SERVER["SCRIPT_NAME"]);
        $pre = "Usage:";
        foreach ($usages as $usage) {
            echo "$pre $script $usage\n";
            $pre = "     |";
        }
        echo "\n";
        echo "Commands:\n";
        foreach ($commands as [$desc, $help]) {
            echo $desc . str_pad(" ", $maxLengthDesc - strlen($desc)) . $help . "\n";
        }
        echo "\n";
        echo "Options:\n";
        foreach ($options as [$desc, $help]) {
            echo $desc . str_pad(" ", $maxLengthDesc - strlen($desc)) . $help . "\n";
        }
        echo "\n";
        echo "Environment variables:\n";
        foreach ($envs as [$desc, $help]) {
            echo $desc . str_pad(" ", $maxLengthDesc - strlen($desc)) . $help . "\n";
        }
        echo "\n";
    }
}
