<?php

declare(strict_types=1);

namespace GitOps;

use GitOps\Service\CommandLineParser;
use GitOps\Service\HttpException;
use GitOps\Service\JsonFormatter;
use GitOps\Service\TextFormatter;
use GitOps\Service\RouteParser;
use Throwable;

final readonly class GitOpsServer
{
	public static function serve(): never
	{
		$request = match (PHP_SAPI) {
			'cli' => (new CommandLineParser)->parse($_SERVER["argv"]),
			default => (new RouteParser)->parse(
				$_SERVER["REQUEST_METHOD"],
				$_SERVER["REQUEST_URI"],
				$_SERVER["HTTP_ACCEPT"],
				$_POST
			),
		};

		try {
			$response = (new GitOpsRequestHandler)->handle($request);
		} catch (HttpException $response) {
			http_response_code($response->getCode());
		} catch (Throwable $response) {
		} finally {
			$formatter = match ($request->accept) {
				"text/plain" => new TextFormatter(),
				"application/json" => new JsonFormatter(),
			};
			header("Content-Type: {$formatter->mimeType()}");
			echo $formatter->format($response);
			exit();
		}
	}
}
