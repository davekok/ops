<?php

declare(strict_types=1);

namespace GitOps;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Command
{
    public const NO_ARGUMENT = 1;
    public const OPTIONAL_ARGUMENT = 2;
    public const REQUIRED_ARGUMENT = 3;

    public string $name;
    public int $order;
    public int $argument;

    public function __construct(
        public string $short,
        public string $help,
        public string|false $usage = false,
    ) {}

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    public function setArgument(int $argument): void
    {
        $this->argument = $argument;
    }
}
