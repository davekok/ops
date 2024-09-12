<?php

declare(strict_types=1);

namespace GitOps\Executor;

use RuntimeException;

final class OptionParser
{
    private string $shortOptions = "";
    private array $longOptions = [];
    private array $index = [];

    public function addOption(string|null $short, string|null $long, mixed $option, bool $flag = false, bool $optional = false): void
    {
        if ($short !== null) {
            if (isset($this->index[$short])) {
                throw new RuntimeException("Option $short already exists.");
            }
            $this->index[$short] = $option;
            $this->shortOptions .= match(true) {
                $flag => $short,
                $optional => "$short::",
                default => "$short:",
            };
        }

        if ($long !== null) {
            $long = Naming::kebabCase($long);
            if (isset($this->index[$long])) {
                throw new RuntimeException("Option $long already exists.");
            }
            $this->index[$long] = $option;
            $this->longOptions[] = match(true) {
                $flag => $long,
                $optional => "$long::",
                default => "$long:",
            };
        }
    }

    public function parse(): iterable
    {
        foreach (getopt($this->shortOptions, $this->longOptions) as $key => $value) {
            yield $key => [$this->index[$key], $value];
        }
    }
}
