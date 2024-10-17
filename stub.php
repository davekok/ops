#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace GitOps;

use Phar;

Phar::interceptFileFuncs();
Phar::mapPhar('gitops');
spl_autoload_register(
	callback: fn(string $class) => match (true) {
		str_starts_with($class, "GitOps\\") => include("phar://gitops/src" . strtr(substr($class, 6), "\\", "/") . ".php"),
		default => false,
	}
);
GitOpsServer::serve();
__HALT_COMPILER();
