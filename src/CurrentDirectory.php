<?php

declare(strict_types=1);

namespace Operations;

use Directory;
use RuntimeException;
use Stringable;

class CurrentDirectory implements Stringable
{
    private string $directory;

    public function __construct()
    {
        $this->directory = getcwd();
    }

    public function set(string $directory): self
    {
        $directory = realpath($directory) ?: throw new RuntimeException("Path does not exists: $directory");
        assert(is_dir($directory), new RuntimeException("Not a directory: $directory"));
        $this->directory = $directory;

        return $this;
    }

    public function open(): Directory
    {
        return dir($this->directory);
    }

    public function __toString(): string
    {
        return $this->directory;
    }
}
