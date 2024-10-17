<?php

declare(strict_types=1);

namespace GitOps\Tests\Service;

use GitOps\Resource\Credentials;
use GitOps\Resource\Help;
use GitOps\Resource\Image;
use GitOps\Resource\Ring;
use GitOps\Service\CommandLineParser;
use GitOps\GitOpsRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitOpsRequest::class)]
#[CoversClass(CommandLineParser::class)]
class CommandLineParserTest extends TestCase
{
	public static function commandLines(): array
	{
		$help = new GitOpsRequest(
			accept: "text/plain",
			workingDirectory: getcwd(),
			resource: Help::class,
			method: "help",
		);

		$credentials = $help->with(["resource" => Credentials::class]);
		$image = $help->with(["resource" => Image::class]);
		$ring = $help->with(["resource" => Ring::class]);

		return [
			["gitops", $help],
			["gitops ?", $help],
			["gitops -?", $help],
			["gitops -h", $help],
			["gitops --help", $help],
			["gitops help", $help],
			["gitops -C /tmp", $help->with(["workingDirectory" => "/tmp"])],
			["gitops help credentials", $credentials],
			["gitops list credentials", $credentials->with(["method" => "list"])],
			["gitops get credentials pull", $credentials->with(["method" => "get", "credentialsType" => "pull"])],
			["gitops get credentials push", $credentials->with(["method" => "get", "credentialsType" => "push"])],
			["gitops set credentials pull dave sdfjksdjkf", $credentials->with(["method" => "set", "credentialsType" => "pull", "username" => "dave", "password" => "sdfjksdjkf"])],
			["gitops set credentials push dave sdfjksdjkf", $credentials->with(["method" => "set", "credentialsType" => "push", "username" => "dave", "password" => "sdfjksdjkf"])],
			["gitops help image", $image],
			["gitops help images", $image],
			["gitops list image", $image->with(["method" => "list"])],
			["gitops list images", $image->with(["method" => "list"])],
			["gitops get image app", $image->with(["method" => "get", "image" => "app"])],
			["gitops get images app", $image->with(["method" => "get", "image" => "app"])],
			["gitops get image app:1.0.0", $image->with(["method" => "get", "image" => "app", "version" => "1.0.0"])],
			["gitops get images app:1.0.0", $image->with(["method" => "get", "image" => "app", "version" => "1.0.0"])],
			["gitops help ring", $ring],
			["gitops help rings", $ring],
			["gitops list ring", $ring->with(["method" => "list"])],
			["gitops list rings", $ring->with(["method" => "list"])],
			["gitops get ring dev", $ring->with(["method" => "get", "ring" => "dev"])],
			["gitops get rings dev", $ring->with(["method" => "get", "ring" => "dev"])],
			["gitops get ring dev:dave", $ring->with(["method" => "get", "ring" => "dev", "fork" => "dave"])],
			["gitops get rings dev:dave", $ring->with(["method" => "get", "ring" => "dev", "fork" => "dave"])],
			["gitops shift ring", $ring->with(["method" => "shift"])],
			["gitops shift rings", $ring->with(["method" => "shift"])],
			["gitops shift ring dev", $ring->with(["method" => "shift", "ring" => "dev"])],
			["gitops shift rings dev", $ring->with(["method" => "shift", "ring" => "dev"])],
			["gitops shift ring dev:dave", $ring->with(["method" => "shift", "ring" => "dev", "fork" => "dave"])],
			["gitops shift rings dev:dave", $ring->with(["method" => "shift", "ring" => "dev", "fork" => "dave"])],
			["gitops unshift ring", $ring->with(["method" => "unshift"])],
			["gitops unshift rings", $ring->with(["method" => "unshift"])],
			["gitops unshift ring dev", $ring->with(["method" => "unshift", "ring" => "dev"])],
			["gitops unshift rings dev", $ring->with(["method" => "unshift", "ring" => "dev"])],
			["gitops unshift ring dev:dave", $ring->with(["method" => "unshift", "ring" => "dev", "fork" => "dave"])],
			["gitops unshift rings dev:dave", $ring->with(["method" => "unshift", "ring" => "dev", "fork" => "dave"])],
			["gitops reconcile ring", $ring->with(["method" => "reconcile"])],
			["gitops reconcile rings", $ring->with(["method" => "reconcile"])],
			["gitops reconcile ring dev", $ring->with(["method" => "reconcile", "ring" => "dev"])],
			["gitops reconcile rings dev", $ring->with(["method" => "reconcile", "ring" => "dev"])],
			["gitops reconcile ring dev:dave", $ring->with(["method" => "reconcile", "ring" => "dev", "fork" => "dave"])],
			["gitops reconcile rings dev:dave", $ring->with(["method" => "reconcile", "ring" => "dev", "fork" => "dave"])],
		];
	}

    #[DataProvider("commandLines")]
	public function testParse(string $commandLine, GitOpsRequest $expected): void
	{
		$parser = new CommandLineParser();
		$actual = $parser->parse(explode(" ", $commandLine));
		$this->assertEquals($expected, $actual);
	}
}
