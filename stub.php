#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Operations;

use Phar;
use Throwable;

try {
    Phar::interceptFileFuncs();
    Phar::mapPhar('ops');
    spl_autoload_register(fn(string $class) => include(str_replace([__NAMESPACE__, "\\"], ["phar://ops/src", "/"], "$class.php")));
    (new Executor(new Main))->execute();
} catch (Throwable $throwable) {
    echo "Error: {$throwable->getMessage()}\n## {$throwable->getFile()}({$throwable->getLine()})\n{$throwable->getTraceAsString()}\n";
} finally {
    exit();
}

__HALT_COMPILER();
