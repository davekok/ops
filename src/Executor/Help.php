<?php

declare(strict_types=1);

namespace GitOps\Executor;

use Throwable;

final class Help
{
    private Throwable|null $throwable = null;

    public function __construct(
        private readonly array $commands = [],
        private readonly array $options = [],
    ) {}

    public function error(Throwable|null $throwable): self
    {
        $this->throwable = $throwable;
        return $this;
    }

    public function __toString(): string
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
            if (is_string($option->env)) {
                $envs[] = ["  $option->env*", $option->help ?? ""];
                continue;
            }
            $long = Naming::kebabCase($option->name);
            $desc = match (true) {
                $option->short !== null => "  -$option->short,--$long",
                default => "  --$long",
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

        $s = "";
        if ($this->throwable !== null) {
            $s .= "Error: {$this->throwable->getMessage()}\n## {$this->throwable->getFile()}({$this->throwable->getLine()})\n{$this->throwable->getTraceAsString()}\n";
        }
        $script = basename($_SERVER["SCRIPT_NAME"]);
        $pre = "Usage:";
        foreach ($usages as $usage) {
            $s .= "$pre $script $usage\n";
            $pre = "     |";
        }
        $s .= "\n";
        $s .= "Commands:\n";
        foreach ($commands as [$desc, $help]) {
            $s .= $desc . str_pad(" ", $maxLengthDesc - strlen($desc)) . $help . "\n";
        }
        $s .= "\n";
        $s .= "Options:\n";
        foreach ($options as [$desc, $help]) {
            $s .= $desc . str_pad(" ", $maxLengthDesc - strlen($desc)) . $help . "\n";
        }
        $s .= "\n";
        $s .= "Environment variables:\n";
        foreach ($envs as [$desc, $help]) {
            $s .= $desc . str_pad(" ", $maxLengthDesc - strlen($desc)) . $help . "\n";
        }
        $s .= "\n";

        return $s;
    }
}
