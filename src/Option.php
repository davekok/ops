<?php

declare(strict_types=1);

namespace GitOps;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Option
{
    public string $name;
    public string $type;
    public mixed $default;
    public bool $flag;

    public function __construct(
        public string|null $short,
        public string $help,
        public string|false $usage = false,
        public bool|string $env = false,
        public array|string|null $values = null,
        public string|null $pattern = null,
        public string|null $filePattern = null,
        public string|null $valueName = null,
        public string|null $morph = null,
    ) {}

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setDefault(mixed $default): void
    {
        $this->default = $default;
    }

    public function setFlag(bool $flag): void
    {
        $this->flag = $flag;
    }
}
