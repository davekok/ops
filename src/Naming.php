<?php

declare(strict_types=1);

namespace Operations;

class Naming
{
    public static function camelCase(string $name): string
    {
        return lcfirst(self::pascalCase($name));
    }

    public static function pascalCase(string $name): string
    {
        return str_replace(["_", "-"], "", ucwords(strtolower($name), "_-"));
    }

    public static function snakeCase(string $name): string
    {
        return preg_replace('/(?<!^)[A-Z]/', '_$0', $name);
    }

    public static function screamingSnakeCase(string $name): string
    {
        return strtoupper(self::snakeCase($name));
    }

    public static function kebabCase(string $name): string
    {
        return preg_replace('/(?<!^)[A-Z]/', '-$0', $name);
    }
}
