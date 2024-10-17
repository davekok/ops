<?php

declare(strict_types=1);

namespace GitOps\Service;

use GitOps\GitOpsResponseFormatter;
use GitOps\GitOpsResponse;
use Throwable;

class JsonFormatter implements GitOpsResponseFormatter
{
	public function mimeType(): string
	{
		return "application/json";
	}

	public function format(GitOpsResponse|Throwable $response): string
	{
		return json_encode(
			value: match (true) {
				$response instanceof Throwable => $this->throwableToArray($response),
				default => $response
			},
			flags: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
	}

	private function throwableToArray(Throwable $throwable): array
	{
		$data = [
			"error" => get_class($throwable),
			"message" => $throwable->getMessage(),
			"code" => $throwable->getCode(),
			"file" => $throwable->getFile(),
			"line" => $throwable->getLine(),
			"trace" => $throwable->getTrace(),
		];

		$previous = $throwable->getPrevious();
		if ($previous) {
			$data["previous"] = $this->throwableToArray($throwable->getPrevious());
		}

		return $data;
	}
}
