<?php

declare(strict_types=1);

namespace GitOps\Resource;

use GitOps\GitOpsResponse;
use GitOps\GitOpsResponseSection;
use GitOps\GitOpsResponseSectionTerm;
use GitOps\Model\WorkingDirectory;
use GitOps\Service\Executor;

class Credentials
{
	public function login(WorkingDirectory $workingDirectory): GitOpsResponse
	{
		$registry = $workingDirectory->gitOps->registry;
		$host = $registry->pushCredentials->host;
		$username = $registry->pushCredentials->username;
		$password = $registry->pushCredentials->password;
		$exec = new Executor();

		return new GitOpsResponse(
			sections: [
				new GitOpsResponseSection(
					title: "Logging in",
					body: [
						new GitOpsResponseSectionTerm(
							term: "buildah",
							def: $exec("buildah login -u '$username' -p '$password' '$host'")
						),
						new GitOpsResponseSectionTerm(
							term: "skopeo",
							def: $exec("skopeo login -u '$username' -p '$password' '$host'")
						),
					]
				),
			]
		);
	}

	public function set(WorkingDirectory $workingDirectory, string $credentialsType, string $username, string $password): GitOpsResponse
	{
		$registry = $workingDirectory->gitOps->registry;
		$credentialsFile = match ($credentialsType) {
			"push" => $registry->pushCredentials,
			"pull" => $registry->pullCredentials,
		};
		$credentialsFile->username = $username;
		$credentialsFile->password = $password;
		$credentialsFile->write();

		return new GitOpsResponse(message: "Credentials saved to $credentialsFile->path");
	}

	public function get(WorkingDirectory $workingDirectory, string $credentialsType): GitOpsResponse
	{
		$registry = $workingDirectory->gitOps->registry;
		$credentialsFile = match ($credentialsType) {
			"push" => $registry->pushCredentials,
			"pull" => $registry->pullCredentials,
		};

		return new GitOpsResponse(
			message: "Credentials",
			sections: [
				new GitOpsResponseSection(
					title: ucfirst($credentialsType) . " credentials",
					body: [
						new GitOpsResponseSectionTerm(
							term: "username",
							def: $credentialsFile->username,
						),
						new GitOpsResponseSectionTerm(
							term: "password",
							def: $credentialsFile->password,
						),
					]
				),
			]
		);
	}

	public function list(WorkingDirectory $workingDirectory): GitOpsResponse
	{
		$registry = $workingDirectory->gitOps->registry;

		return new GitOpsResponse(
			message: "Credentials",
			sections: [
				new GitOpsResponseSection(
					title: "Push credentials",
					body: [
						new GitOpsResponseSectionTerm(
							term: "username",
							def: $registry->pushCredentials->username,
						),
						new GitOpsResponseSectionTerm(
							term: "password",
							def: $registry->pushCredentials->password,
						),
					]
				),
				new GitOpsResponseSection(
					title: "Pull credentials",
					body: [
						new GitOpsResponseSectionTerm(
							term: "username",
							def: $registry->pullCredentials->username,
						),
						new GitOpsResponseSectionTerm(
							term: "password",
							def: $registry->pullCredentials->password,
						),
					]
				),
			]
		);
	}

	public function help(): GitOpsResponse
	{
		return new GitOpsResponse(
			message: "Credentials",
			sections: [
				new GitOpsResponseSection(
					title: "Methods",
					body: [
						new GitOpsResponseSectionTerm(term: "login", def: "login to registry"),
						new GitOpsResponseSectionTerm(term: "list", def: "list credentials files"),
						new GitOpsResponseSectionTerm(term: "get", def: "get username and password from credentials file"),
						new GitOpsResponseSectionTerm(term: "set", def: "update credentials file with username and password"),
					]
				),
				new GitOpsResponseSection(
					title: "Usages",
					body: [
						"gitops login [credentials]",
						"gitops list credentials",
						"gitops get credentials <push|pull>",
						"gitops set credentials <push|pull> <username> <password>",
					]
				),
				new GitOpsResponseSection(
					title: "Routes",
					body: [
						new GitOpsResponseSectionTerm(term: "POST", def: "https://<gitops-host>/credentials/login"),
						new GitOpsResponseSectionTerm(term: "GET", def: "https://<gitops-host>/credentials/list"),
						new GitOpsResponseSectionTerm(term: "GET", def: "https://<gitops-host>/credentials/<push|pull>"),
						new GitOpsResponseSectionTerm(term: "POST", def: "https://<gitops-host>/credentials/<push|pull> {\"username\":\"<username>\",\"password\":\"<password>\"}"),
					]
				),
			]
		);
	}
}
