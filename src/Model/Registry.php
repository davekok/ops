<?php

declare(strict_types=1);

namespace GitOps\Model;

use GitOps\Service\PathTemplate;

readonly class Registry
{
	public CredentialsFile $pushCredentials;
	public CredentialsFile $pullCredentials;
	public PathTemplate $imageUri;
	/** @var Image[] */ public array $images;

	public function __construct(
		public string $host = "ghcr.io",
		string $imageUri = "{host}/example/my-app{/name,fork}",
	) {
		$this->imageUri = new PathTemplate($imageUri, ["host" => $this->host]);
	}

	public function setPushCredentials(CredentialsFile $credentialsFile): void
	{
		$this->pushCredentials = $credentialsFile;
	}

	public function setPullCredentials(CredentialsFile $credentialsFile): void
	{
		$this->pullCredentials = $credentialsFile;
	}

	public function setImages(array $images): void
	{
		$this->images = $images;
	}
}
