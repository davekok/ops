<?php

declare(strict_types=1);

namespace GitOps;


use Throwable;

interface GitOpsResponseFormatter
{
	public function mimeType(): string;
	public function format(GitOpsResponse|Throwable $response): string;
}
