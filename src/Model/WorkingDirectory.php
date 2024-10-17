<?php

declare(strict_types=1);

namespace GitOps\Model;

use GitOps\Attribute\Factory;
use GitOps\Service\WorkingDirectoryFactory;
use Stringable;

#[Factory(WorkingDirectoryFactory::class)]
final readonly class WorkingDirectory implements Stringable
{

	public function __construct(
		public string $directory,
		public GitOps|null $gitOps = null,
		public LocalSettings|null $localSettings = null,

	) {}

	public function __toString(): string
	{
		return $this->directory;
	}
}
