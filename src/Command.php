<?php

declare(strict_types=1);

namespace Operations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Command
{
    public string $name;
    public int $order;

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
}
