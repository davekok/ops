<?php

declare(strict_types=1);

namespace GitOps;

/**
 * Abstract data model of a response.
 */
final readonly class GitOpsResponse
{
	/**
	 * @param string|null $message
	 * @param list<GitOpsResponseSection> $sections
	 */
	public function __construct(
		public string|null $message = null,
		public array $sections = [],
	) {}
}
