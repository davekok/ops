<?php

declare(strict_types=1);

namespace Operations;

final readonly class EnvParser
{
    public function __construct(
        private CurrentDirectory $currentDir,
    ) {}

    public function parse(): iterable
    {
        $pattern = '/^([A-Z_][A-Z0-9_]*) *=(?: *"((?:[ !#-\[\]-~]|\x5C\x5C|\x5C")*)" *| *\'((?:[ !#-\[\]-~]|\x5C\x5C|\x5C\')*)\' *|([ -~]*))$/';
        $dir = $this->currentDir->open();
        for ($entry = $dir->read(); $entry !== false; $entry = $dir->read()) {
            if (!str_starts_with($entry, ".env")) {
                continue;
            }
            $lines = file("$this->currentDir/$entry", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (preg_match($pattern, $line, $matches) === 1) {
                    $key = $matches[1];
                    $value = match (true) {
                        (bool)($matches[2] ?? false) => stripcslashes($matches[2]),
                        (bool)($matches[3] ?? false) => stripcslashes($matches[3]),
                        (bool)($matches[4] ?? false) => trim($matches[4]),
                    };
                    yield $key => $value;
                }
            }
        }
    }
}
