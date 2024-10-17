<?php

declare(strict_types=1);

namespace GitOps\Service;

use GitOps\GitOpsRequest;
use GitOps\Resource;
use LogicException;

class RouteParser
{
	public string $rule;
	public array $args;

	public function parse(string $method, string $url, string $accept = "application/json", array $body = []): GitOpsRequest
	{
		$parts = parse_url($url);
		$path = $parts["path"];

		$this->rule = match($method) {
			"GET" => "G",
			"POST" => "P",
			default => throw new HttpException("Unsupported method: $method"),
		};

		foreach (explode("/", ltrim($path, "/")) as $pathPart) {
			$this->rule .= match ($pathPart) {
				"help" => "?",
				"cred", "credentials" => "c",
				"image", "images" => "i",
				"ring", "rings" => "r",
				"site", "sites" => "t",
				"config" => "f",
				// Based on context parse dynamic argument and reduce to token.
				default => match (substr($this->rule, -1, 1)) {
					"c" => $this->parseCredentialsType($pathPart),
					"r", "b" => $this->parseRing($pathPart),
					"i" => $this->parseImage($pathPart),
					"t" => $this->parseSite($pathPart),
					default => throw new LogicException("Unknown argument $pathPart"),
				},
			};
		}

		if (isset($body["username"])) {
			$this->rule .= $this->parseUsername($body["username"]);
		}

		if (isset($body["password"])) {
			$this->rule .= $this->parsePassword($body["password"]);
		}

		if ($this->rule === "") {
			$this->rule = "g?";
		}

		// Set command from known rules or throw exception.
		[$resource, $method] = match ($this->rule) {
			"G?" => [Resource\Help::class, "help"], // help
			// images
			"Gi?", "G?i" => [Resource\Image::class, "help"], // get image help or get help image
			"Gi" => [Resource\Image::class, "list"], // list images
			"Gi!" => [Resource\Image::class, "get"], // get image <image>
			// rings
			"Gr0" => [Resource\Ring::class, "get"], // get ring [<ring>]
			"Gr" => [Resource\Ring::class, "list"], // list ring
			"Pr0>" => [Resource\Ring::class, "shift"], // post ring <ring> shift
			"Pr0<" => [Resource\Ring::class, "unshift"], // post ring <ring> unshift
			"Pr0~" => [Resource\Ring::class, "reconcile"], // post ring <ring> reconcile
			"Gr?", "G?r" => [Resource\Ring::class, "help"], // get ring help or get help ring
			// sites
			"Gt" => [Resource\Site::class, "list"], // get site
			"Gt$" => [Resource\Site::class, "get"], // get site <site>
			"Pt\$d" => [Resource\Site::class, "deploy"], // post site <site> deploy
			"Pt\$D" => [Resource\Site::class, "setup"], // post site <site> setup
			"Gt?","G?t" => [Resource\Site::class, "help"], // get site help or get help site
			// config
			"Gf?", "G?f" => [Resource\Config::class, "help"], // get config help or get help config
			"Gf" => [Resource\Config::class, "list"], // get config
			"GfD" => [Resource\Config::class, "setup"], // get config setup
			// credentials
			"Gc?", "G?c" => [Resource\Credentials::class, "help"], // get credentials help or get help credentials
			"Gc" => [Resource\Credentials::class, "list"], // list credentials
			"GcC" => [Resource\Credentials::class, "get"], // get credentials <type>
			"PcCup" => [Resource\Credentials::class, "set"], // post credentials <type> <username> <password>
			default => throw new LogicException("Unrecognized url: $method $url"),
		};

		return new GitOpsRequest(
			accept: $this->parseAccept($accept),
			workingDirectory: $this->args["workingDirectory"] ?? getcwd(),
			resource: $resource,
			method: $method,
			credentialsType: $this->args["credentialsType"] ?? null,
			username: $this->args["username"] ?? null,
			password: $this->args["password"] ?? null,
			image: $this->args["image"] ?? null,
			version: $this->args["version"] ?? null,
			site: $this->args["site"] ?? null,
			ring: $this->args["ring"] ?? null,
			fork: $this->args["fork"] ?? null,
		);
	}

	private function parseAccept(string $arg): string
	{
		return match ($arg) {
			"text/plain" => "text/plain",
			default => "application/json",
		};
	}

	private function parseWorkingDirectory(string $arg): string
	{
		$path = realpath($arg);
		if (!is_string($path)) {
			throw new LogicException("Invalid argument for working directory, path not found or not valid: $arg");
		}
		$this->args["workingDirectory"] = $path;
		// strip . from the rule
		$this->rule = substr($this->rule, 0, -1);
		// ignore token
		return "";
	}

	private function parseCredentialsType(string $arg): string
	{
		if (!in_array($arg, ["push", "pull"], true)) {
			throw new LogicException("Invalid credentials type expected 'push' or 'pull', got: $arg");
		}
		$this->args["credentialsType"] = $arg;

		return "C";
	}

	private function parseUsername(string $arg): string
	{
		if (preg_match("/^[A-Za-z0-9_.@-]+$/", $arg) !== 1) {
			throw new LogicException("Invalid user name: $arg");
		}
		$this->args["username"] = $arg;

		return "u";
	}

	private function parsePassword(string $arg): string
	{
		if (preg_match("/^[ -~]+$/", $arg) !== 1) {
			throw new LogicException("Invalid password: $arg");
		}
		$this->args["password"] = $arg;

		return "p";
	}

	private function parseImage(string $arg): string
	{
		if (preg_match("/^([a-z0-9-]+)(?::([a-z0-9.-]+))?$/", $arg, $matches) !== 1) {
			throw new LogicException("Invalid image: $arg");
		}

		$this->args["image"] = $matches[1];
		$this->args["version"] = $matches[2] ?? null;

		return "!";
	}

	private function parseSite(string $arg): string
	{
		if (preg_match("/^[a-z0-9-]+$/", $arg) !== 1) {
			throw new LogicException("Invalid site: $arg");
		}
		$this->args["site"] = $arg;

		return "$";
	}

	private function parseRing(string $arg): string
	{
		if (preg_match("/^([a-z0-9-]+)(?::([a-z0-9-]+))?$/", $arg, $matches) !== 1) {
			throw new LogicException("Invalid ring: $arg");
		}
		$this->args["ring"] = $matches[1];
		$this->args["fork"] = $matches[2] ?? null;

		return "0";
	}
}
