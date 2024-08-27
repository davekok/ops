<?php

declare(strict_types=1);

namespace Operations;

readonly class Image
{
    public function __construct(
        public string $registry,
        public string $project,
        public string $name,
        public string $containerFile,
        public bool $sortLast,
        public bool $requiresUpdate,
    ) {}

    public function getRepository(): Repository
    {
        return new Repository("$this->registry/$this->project/$this->name");
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
