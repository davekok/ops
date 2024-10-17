<?php

declare(strict_types=1);

namespace GitOps;

/**
 * Abstract data model of a request.
 */
final readonly class GitOpsRequest
{
	public function __construct(
		public string $accept,
		public string $workingDirectory,
		public string $resource,
		public string $method,
		public ?string $credentialsType = null,
		public ?string $username = null,
		public ?string $password = null,
		public ?string $image = null,
		public ?string $version = null,
		public ?string $site = null,
		public ?string $ring = null,
		public ?string $fork = null,
	) {}

	public function with(array $data): self
	{
		return new self(
			accept: $data["accept"] ?? $this->accept,
			workingDirectory: $data["workingDirectory"] ?? $this->workingDirectory,
			resource: $data["resource"] ?? $this->resource,
			method: $data["method"] ?? $this->method,
			credentialsType: $data["credentialsType"] ?? $this->credentialsType,
			username: $data["username"] ?? $this->username,
			password: $data["password"] ?? $this->password,
			image: $data["image"] ?? $this->image,
			version: $data["version"] ?? $this->version,
			site: $data["site"] ?? $this->site,
			ring: $data["ring"] ?? $this->ring,
			fork: $data["fork"] ?? $this->fork,
		);
	}
}
