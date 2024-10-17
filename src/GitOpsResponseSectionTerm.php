<?php

declare(strict_types=1);

namespace GitOps;

final readonly class GitOpsResponseSectionTerm
{
	public function __construct(
		public string $term,
		public string|null $def,
	) {}
}
