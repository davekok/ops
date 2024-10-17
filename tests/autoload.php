<?php

declare(strict_types=1);

spl_autoload_register(
	callback: fn(string $class) => match (true) {
		str_starts_with($class, "GitOps\\Tests\\") => include(__DIR__ . "/tests" . strtr(substr($class, 12), "\\", "/") . ".php"),
		str_starts_with($class, "GitOps\\") => include(dirname(__DIR__) . "/src" . strtr(substr($class, 6), "\\", "/") . ".php"),
		default => false,
	}
);
