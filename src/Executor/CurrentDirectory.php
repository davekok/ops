<?php

declare(strict_types=1);

namespace GitOps\Executor;

use IteratorAggregate;
use RuntimeException;
use Stringable;
use Traversable;

class CurrentDirectory implements Stringable, IteratorAggregate
{
    private string $directory;

    public function __construct()
    {
        $this->directory = getcwd();
    }

    public function set(string $directory): self
    {
        $directory = realpath($directory);
        assert(is_string($directory), new RuntimeException("Path does not exists: $directory"));
        assert(is_dir($directory), new RuntimeException("Not a directory: $directory"));
        $this->directory = $directory;

        return $this;
    }

    public function getIterator(): Traversable
    {
        $dir = dir($this->directory);
        for ($entry = $dir->read(); $entry !== false; $entry = $dir->read()) {
            if ($entry === "." || $entry === "..") {
                continue;
            }

            yield $entry;
        }
    }

    public function __toString(): string
    {
        return $this->directory;
    }
}
