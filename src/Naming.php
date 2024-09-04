<?php

declare(strict_types=1);

namespace GitOps;

class Naming
{
    private const CAMEL_CASE = 0;
    private const PASCAL_CASE = 1;
    private const SNAKE_CASE = 3;
    private const SCREAMING_SNAKE_CASE = 4;
    private const KEBAB_CASE = 5;
    private const SCREAMING_KEBAB_CASE = 6;

    public static function camelCase(string $text, int $caseType = self::CAMEL_CASE): string
    {
        return match ($caseType) {
            self::CAMEL_CASE => $text,
            self::PASCAL_CASE => lcfirst($text),
            self::SNAKE_CASE, self::SCREAMING_SNAKE_CASE => lcfirst(str_replace("_", "", ucwords(strtolower($text), "_"))),
            self::KEBAB_CASE, self::SCREAMING_KEBAB_CASE => lcfirst(str_replace("-", "", ucwords(strtolower($text), "-"))),
        };
    }

    public static function pascalCase(string $text, int $caseType = self::CAMEL_CASE): string
    {
        return match ($caseType) {
            self::CAMEL_CASE => ucfirst($text),
            self::PASCAL_CASE => $text,
            self::SNAKE_CASE, self::SCREAMING_SNAKE_CASE => str_replace("_", "", ucwords(strtolower($text), "_")),
            self::KEBAB_CASE, self::SCREAMING_KEBAB_CASE => str_replace("-", "", ucwords(strtolower($text), "-")),
        };
    }

    public static function snakeCase(string $text, int $caseType = self::CAMEL_CASE): string
    {
        return match ($caseType) {
            self::CAMEL_CASE => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $text)),
            self::PASCAL_CASE => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', lcfirst($text))),
            self::SNAKE_CASE => $text,
            self::SCREAMING_SNAKE_CASE => strtolower($text),
            self::KEBAB_CASE => str_replace("-", "_", $text),
            self::SCREAMING_KEBAB_CASE => strtolower(str_replace("-", "_", $text)),
        };
    }

    public static function screamingSnakeCase(string $text, int $caseType = self::CAMEL_CASE): string
    {
        return match ($caseType) {
            self::CAMEL_CASE => strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', $text)),
            self::PASCAL_CASE => strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', lcfirst($text))),
            self::SNAKE_CASE => strtoupper($text),
            self::SCREAMING_SNAKE_CASE => $text,
            self::KEBAB_CASE => strtoupper(str_replace("-", "_", $text)),
            self::SCREAMING_KEBAB_CASE => str_replace("-", "_", $text),
        };
    }

    public static function kebabCase(string $text, int $caseType = self::CAMEL_CASE): string
    {
        return match ($caseType) {
            self::CAMEL_CASE => strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $text)),
            self::PASCAL_CASE => strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', lcfirst($text))),
            self::SNAKE_CASE => str_replace("_", "-", $text),
            self::SCREAMING_SNAKE_CASE => strtolower(str_replace("_", "-", $text)),
            self::KEBAB_CASE => $text,
            self::SCREAMING_KEBAB_CASE => strtolower($text),
        };
    }

    public static function screamingKebabCase(string $text, int $caseType = self::CAMEL_CASE): string
    {
        return match ($caseType) {
            self::CAMEL_CASE => strtoupper(preg_replace('/(?<!^)[A-Z]/', '-$0', $text)),
            self::PASCAL_CASE => strtoupper(preg_replace('/(?<!^)[A-Z]/', '-$0', lcfirst($text))),
            self::SNAKE_CASE => strtoupper(str_replace("_", "-", $text)),
            self::SCREAMING_SNAKE_CASE => str_replace("_", "-", $text),
            self::KEBAB_CASE => strtoupper($text),
            self::SCREAMING_KEBAB_CASE => $text,
        };
    }
}
