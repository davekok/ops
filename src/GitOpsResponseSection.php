<?php

declare(strict_types=1);

namespace GitOps;

/**
 * Abstract data model of a response section.
 */
final readonly class GitOpsResponseSection
{
	/**
	 * @param string|list<string>|list<GitOpsResponseSectionTerm> $body
	 */
	public function __construct(
		public string $title,
		public array|string $body,
	) {}
}
