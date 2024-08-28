<?php

declare(strict_types=1);

namespace Operations;

final readonly class EnvParser
{
    private const PATTERN = '/^([A-Z_][A-Z0-9_]*) *=(?: *"((?:[ !#-\[\]-~]|\x5C\x5C|\x5C")*)" *| *\'((?:[ !#-\[\]-~]|\x5C\x5C|\x5C\')*)\' *|([ -~]*))$/';

    public function __construct(
        private CurrentDirectory $currentDir,
    ) {}

    public function parse(): iterable
    {
        foreach ($this->currentDir as $entry) {
            if (!str_starts_with($entry, ".env")) {
                continue;
            }
            foreach (file("$this->currentDir/$entry", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (preg_match(self::PATTERN, $line, $matches) === 1) {
                    yield $matches[1] => match (true) {
                        (bool)($matches[2] ?? false) => stripcslashes($matches[2]),
                        (bool)($matches[3] ?? false) => stripcslashes($matches[3]),
                        (bool)($matches[4] ?? false) => trim($matches[4]),
                    };
                }
            }
        }
    }
}
