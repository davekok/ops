<?php

declare(strict_types=1);

namespace GitOps\Model;

final readonly class LocalSettings
{
	public function __construct(
		public string $ring,
		public string $site,
	) {}
}
