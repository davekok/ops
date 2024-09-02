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

    /** @var array<string,Option> */
    private array $options;

    public function __construct(object $object)
    {
        $currentDir = new CurrentDirectory();
        $this->program = new ProgramReflector($object, $currentDir);
        $this->optionParser = new OptionParser();
        $this->envParser = new EnvParser($currentDir);
    }

    /** @throws ReflectionException */
    public function execute(): void
    {
        $this->options = $this->getOptions();
        $help = new Help($this->getCommands(), $this->options);
        $this->parseEnv();

        try {
            $commands = $this->parseOptions();
        } catch (Throwable $throwable) {
            echo $help->error($throwable);
            return;
        }

        if (count($commands) === 0) {
            echo $help;
            return;
        }

        foreach ($commands as [$command, $value]) {
            if ($command->name === "help") {
                echo $help;
                continue;
            }
            $this->program->invoke($command, $value);
        }
    }

    private function getCommands(): array
    {
        $commands = [];

        foreach ($this->program->getCommands() as $command) {
            $this->optionParser->addOption(
                short: $command->short,
                long: $command->name,
                option: $command,
                flag: $command->argument === Command::NO_ARGUMENT,
                optional: $command->argument === Command::OPTIONAL_ARGUMENT
            );
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
                $methods[$commandOrOption->order] = [$commandOrOption, $value];
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
        #     CHCK_ANALYZE="phstan analyze ."
        #     CHCK_TEST="phpunit ./tests"
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
}
