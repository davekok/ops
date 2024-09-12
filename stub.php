#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace GitOps;

use GitOps\Executor\Executor;
use Phar;
use Throwable;

try {
    Phar::interceptFileFuncs();
    Phar::mapPhar('gitops');
    spl_autoload_register(fn(string $class) => include(str_replace([__NAMESPACE__, "\\"], ["phar://gitops/src", "/"], "$class.php")));
    (new Executor(new Main))->execute();
} catch (Throwable $throwable) {
    echo "Error: {$throwable->getMessage()}\n## {$throwable->getFile()}({$throwable->getLine()})\n{$throwable->getTraceAsString()}\n";
} finally {
    exit();
}

__HALT_COMPILER();
