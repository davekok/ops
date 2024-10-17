<?php

declare(strict_types=1);

namespace GitOps\Service;

use GitOps\GitOpsRequest;
use GitOps\Resource;
use LogicException;

class CommandLineParser
{
	public string $rule;
	public array $args;

	public function parse(array $argv): GitOpsRequest
	{
		$this->rule = "";
		$this->args = [];

		// Reduce arguments to tokens and build up a rule of tokens.
		$argc = count($argv);
		for ($i = 1; $i < $argc; ++$i) {
			$arg = $argv[$i];
			$this->rule .= match ($arg) {
				"-O", "--output-format" => "O",
				"-C", "--working-directory" => ".",
				"<<", "unshift" => "<",
				">>", "shift" => ">",
				"recon", "reconcile" => "~",
				"cred", "credentials" => "c",
				"deploy" => "d",
				"get" => "g",
				"login" => "L",
				"list" => "l",
				"help", "--help", "-h", "-?", "?" => "?",
				"image", "images" => "i",
				"ring", "rings" => "r",
				"set" => "s",
				"setup" => "D",
				"site", "sites" => "t",
				"init" => "n",
				"config" => "f",
				// Based on context parse dynamic argument and reduce to token.
				default => match (substr($this->rule, -1, 1)) {
					"O" => $this->parseOutputFormat($arg),
					"." => $this->parseWorkingDirectory($arg),
					"c" => $this->parseCredentialsType($arg),
					"C" => $this->parseUsername($arg),
					"u" => $this->parsePassword($arg),
					"r", "b" => $this->parseRing($arg),
					"i" => $this->parseImage($arg),
					"s" => $this->parseSite($arg),
					default => throw new LogicException("Unknown argument $arg"),
				},
			};
		}

		if ($this->rule === "") {
			$this->rule = "?";
		}

		// Set command from known rules or throw exception.
		[$resource, $method] = match ($this->rule) {
			"?" => [Resource\Help::class, "help"], // help
			// images
			"?i" => [Resource\Image::class, "help"], // help image
			"li" => [Resource\Image::class, "list"], // list image
			"gi!" => [Resource\Image::class, "get"], // get image <image>
			// rings
			"sr0" => [Resource\Ring::class, "set"], // set ring <ring>
			"gr", "gr0" => [Resource\Ring::class, "get"], // get ring [<ring>]
			"lr" => [Resource\Ring::class, "list"], // list ring
			">r", ">r0" => [Resource\Ring::class, "shift"], // shift ring [<ring>]
			"<r", "<r0" => [Resource\Ring::class, "unshift"], // unshift ring [<ring>]
			"~r", "~r0" => [Resource\Ring::class, "reconcile"], // reconcile ring [<ring>]
			"?r" => [Resource\Ring::class, "help"], // help ring
			// sites
			"st$" => [Resource\Site::class, "set"], // set site <site>
			"gt", "gt$" => [Resource\Site::class, "get"], // get site [<site>]
			"lt" => [Resource\Site::class, "list"], // list site
			"dt", "dt$" => [Resource\Site::class, "deploy"], // deploy site [<site>]
			"Dt$" => [Resource\Site::class, "setup"], // setup site <site>
			"?t" => [Resource\Site::class, "help"], // help site
			// config
			"?f" => [Resource\Config::class, "help"], // help config
			"gf", "lf" => [Resource\Config::class, "list"], // list config
			"Df" => [Resource\Config::class, "setup"], // setup config
			// credentials
			"?c" => [Resource\Credentials::class, "help"], // help credentials
			"lc" => [Resource\Credentials::class, "list"], // list credentials
			"gcC" => [Resource\Credentials::class, "get"], // get credentials <type>
			"scCup" => [Resource\Credentials::class, "set"], // set credentials <type> <username> <password>
			"L","Lc" => [Resource\Credentials::class, "login"], // login credentials
			default => throw new LogicException("Unrecognized command: " . implode(" ", $argv)),
		};

		return new GitOpsRequest(
			accept: $this->args["accept"] ?? "text/plain",
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

	private function parseOutputFormat(string $arg): string
	{
		$this->args["accept"] = match ($arg) {
			"text" => "text/plain",
			"json" => "application/json",
			default => throw new LogicException("Invalid output format expected 'text' or 'json', got: $arg"),
		};

		return "O";
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
		$this->args["credentialsType"] = match ($arg) {
			"push", "pull" => $arg,
			default => throw new LogicException("Invalid credentials type expected 'push' or 'pull', got: $arg"),
		};

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
