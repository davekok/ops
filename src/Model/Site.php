<?php

declare(strict_types=1);

namespace GitOps\Model;

final readonly class Site
{
	public function __construct(
		public string $name = "example",
		public string $namespace = "example",
		public string $environment = "example",
		public string $ring = "example",
		public string $probeInterval = "5m",
	) {}
}
