<?php

declare(strict_types=1);

namespace GitOps\Attribute;

use Attribute;
use LogicException;
use RuntimeException;

#[Attribute(Attribute::TARGET_PARAMETER|Attribute::TARGET_PROPERTY)]
final readonly class ArrayType
{
    public bool $isBuiltin;

    public function __construct(
        public string $name,
        public bool $map = false
    ) {
        assert(
            !in_array($this->name, ["null", "false", "true", "array", "resource", "void", "never", "callable", "iterable", "mixed"], true),
            new RuntimeException("Not allowed builtin used for array type: $this->name")
        );
        $this->isBuiltin = in_array($this->name, ["bool", "int", "float", "string"], true);
        if (!$this->isBuiltin) {
            assert(class_exists($this->name), new RuntimeException("Type $this->name not recognized."));
        }
    }

    public function is(mixed $value): bool
    {
        if (!$this->isBuiltin) {
            return $value instanceof $this->name;
        }

        return match ($this->name) {
            "bool" => is_bool($value),
            "int" => is_int($value),
            "float" => is_float($value),
            "string" => is_string($value),
        };
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
