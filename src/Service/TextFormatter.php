<?php

declare(strict_types=1);

namespace GitOps\Service;

use GitOps\GitOpsResponse;
use GitOps\GitOpsResponseFormatter;
use GitOps\GitOpsResponseSection;
use LogicException;
use Throwable;

final readonly class TextFormatter implements  GitOpsResponseFormatter
{
	private const RED = "\033[31;49m";
	private const GREEN = "\033[32;49m";
	private const YELLOW = "\033[33;49m";
	private const GREY = "\033[90;49m";
	private const NORMAL = "\033[39;49m";

	public function mimeType(): string
	{
		return "text/plain; charset=UTF-8";
	}

	public function format(GitOpsResponse|Throwable $response): string
	{
		if ($response instanceof LogicException) {
			return self::RED . "Error" . self::NORMAL . ": {$response->getMessage()}\n\n";
		}

		if ($response instanceof Throwable) {
			$error = get_class($response);

			return self::RED . $error . self::NORMAL . ": {$response->getMessage()}\n\n"
				. "## {$response->getFile()}({$response->getLine()})\n"
				. $response->getTraceAsString() . "\n\n";
		}

		$text = "";

		if (isset($response->message)) {
			$text .= self::NORMAL . $response->message . "\n\n";
		}

		foreach ($response->sections as $section) {
			assert($section instanceof GitOpsResponseSection);

			$text .= self::YELLOW . $section->title. ":" . self::NORMAL . "\n\n";

			if (is_string($section->body)) {
				$text .= wordwrap($section->body, 100, PHP_EOL) . "\n\n";
				continue;
			}

			if (is_string($section->body[0])) {
				foreach ($section->body as $line) {
					$text .= "  " . $line . "\n";
				}
				$text .= "\n";
				continue;
			}

			$maxLengthTerm = 0;
			foreach ($section->body as $def) {
				$lengthTerm = strlen($def->term);
				if ($lengthTerm > $maxLengthTerm) {
					$maxLengthTerm = $lengthTerm;
				}
			}

			$maxLengthTerm += 3;
			foreach ($section->body as $def) {
				$text .= "  " . self::GREEN . $def->term . self::NORMAL . str_repeat(" ", $maxLengthTerm - strlen($def->term));
				$text .= $def->def ?? (self::GREY . "<blank>" . self::NORMAL);
				$text .= "\n";
			}
			$text .= "\n";
		}

		return $text;
	}
}
