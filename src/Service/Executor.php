<?php

declare(strict_types=1);

namespace GitOps\Service;

use LogicException;

class Executor
{
	public function __invoke(string $command): string
	{
		$result = exec($command, $output, $result_code);
        if ($result === false || $result_code !== 0) {
            throw new LogicException("command failed: $command");
        }

		return implode("\n", $output);
	}
}
