<?php

declare(strict_types=1);

namespace GitOps\Service;

use LogicException;

/**
 * Loosely based on UriTemplates.
 *
 * Below syntax examples and how they expand if var1 is set to foo, and var2 is set to bar.
 *
 *  Syntax                     | Expands
 * ----------------------------|---------------------
 *  /path/{var1}/subpath       | /path/foo/subpath
 *  /path{/var1}/subpath       | /path/foo/subpath
 *  /path{/var1,var2}/subpath  | /path/foo/bar/subpath
 */
class PathTemplate
{
	private const TEXT = 0;
	private const PLAIN_VAR = 1;
	private const SLASH_VAR = 2;
	private readonly array $sections;
	private array $vars;

	public function __construct(
		public readonly string $template,
		array $vars = [],
	)
	{
		$this->vars = $this->checkVars($vars);

		$found = preg_match_all("#\{(/)?([A-Za-z0-9]+)(?:,([A-Za-z0-9]+))*}#", $template, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		if ($found === 0) {
			$this->sections = [];
			return;
		}

		$section = 0;
		$last = 0;
		$sections = [];
		foreach ($matches as $match) {
			$expression = $match[0][0];
			$position = (int)$match[0][1];
			$length = strlen($expression);
			$before = substr($template, $last, $position - $last);
			if ($before === "" && $last > 0) {
				throw new LogicException("A path template expression should not immediately follow another: $this->template");
			}
			$last = $position + $length;
			$type = $match[1][0] === "/" ? self::SLASH_VAR : self::PLAIN_VAR;
			array_shift($match);
			array_shift($match);
			$vars = array_values(array_column($match, 0));
			if ($type === self::PLAIN_VAR && count($vars) > 1) {
				throw new LogicException("A plain path template expression should contain only one variable: $expression");
			}
			if ($before !== "") {
				$sections[$section++] = [self::TEXT, $before];
			}
			if ($type === self::PLAIN_VAR) {
				$sections[$section++] = [$type, $vars[0]];
			}
			if ($type === self::SLASH_VAR) {
				$sections[$section++] = [$type, $vars];
			}
		}

		$after = substr($template, $last);
		if ($after !== "") {
			$sections[$section] = [0, $after];
		}

		$this->sections = $sections;
	}

	public function set(string $name, string $value): void
	{
		$this->vars[$name] = $value;
	}

	public function expand(array $vars = []): string
	{
		$vars = [...$this->vars, ...$this->checkVars($vars)];

		$path = "";
		foreach ($this->sections as [$type, $arg]) {
			switch ($type) {
				case self::TEXT:
					$path .= $arg;
					continue 2;

				case self::PLAIN_VAR:
					if (isset($vars[$arg]) && $vars[$arg] !== "") {
						$path .= $vars[$arg];
					}
					continue 2;

				case self::SLASH_VAR:
					foreach ($arg as $var) {
						if (isset($vars[$var]) && $vars[$var] !== "") {
							$path .= "/" . $vars[$var];
						}
					}
					continue 2;
			}
		}

		return $path;
	}

	public function extract(string $path): array
	{
		$mismatchMessage = "Path '$path' does not match path template '$this->template'.";

		$vars = [];

		for ($section = count($this->sections) - 1; $section >= 0; --$section) {
			[$type, $arg] = $this->sections[$section];
			switch ($type) {
				case self::TEXT:
					if (!str_ends_with($path, $arg)) {
						throw new LogicException($mismatchMessage);
					}
					$path = substr($path, 0, -strlen($arg));
					continue 2;

				case self::PLAIN_VAR:
					[$path, $value] = $this->extractVarValue($path, $section, $mismatchMessage);
					if (preg_match("#^[A-Za-z0-9._-]*$#", $value) !== 1) {
						throw new LogicException($mismatchMessage);
					}
					$vars[$arg] = $value;
					continue 2;

				case self::SLASH_VAR:
					[$path, $value] = $this->extractVarValue($path, $section, $mismatchMessage);
					$values = explode("/", ltrim($value, "/"));
					foreach ($arg as $ix => $var) {
						$value = $values[$ix] ?? "";
						if (preg_match("#^[A-Za-z0-9._-]*$#", $value) !== 1) {
							throw new LogicException($mismatchMessage);
						}
						$vars[$var] = $value;
					}
					continue 2;
			}
		}

		return $vars;
	}

	private function checkVars(array $vars): array
	{
		foreach ($vars as $key => $value) {
			if (preg_match('/^[A-Za-z0-9._-]*$/', $value) !== 1) {
				throw new LogicException("A path template variable must contain safe characters only [A-Za-z0-9._-], path template: $this->template, variable: $key, value: $value");
			}
		}

		return $vars;
	}

	/**
	 * @return array{string,string}
	 */
	private function extractVarValue(string $path, int $section, string $mismatchMessage): array
	{
		if ($section === 0) {
			return ["", $path];
		}

		[$prevType, $prevArg] = $this->sections[$section - 1];
		if ($prevType !== self::TEXT) {
			throw new LogicException("Internal logic exception in path template.");
		}

		$p = strrpos($path, $prevArg);
		if ($p === false) {
			throw new LogicException($mismatchMessage);
		}

		$p += strlen($prevArg);

		$value = substr($path, $p);
		$path = substr($path, 0, $p);

		return [$path, $value];
	}
}
