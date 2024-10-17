<?php

declare(strict_types=1);

namespace GitOps\Model;

use GitOps\Service\PathTemplate;

readonly class PathTemplates
{
	public PathTemplate $pushCredentialsFile;
	public PathTemplate $pullCredentialsFile;
	public PathTemplate $localSettings;
	public PathTemplate $kustomization;
	public PathTemplate $containerFile;

    public function __construct(
        string $pushCredentialsFile = "{root}/.gitops-push-credentials",
        string $pullCredentialsFile = "{root}/.gitops-pull-credentials",
        string $localSettings = "{root}/.gitops-local",
        string $kustomization = "{root}/etc/{environment}/kustomization.yaml",
        string $containerFile = "{root}/etc/{image}.containerfile",
    ) {
		$this->pushCredentialsFile = new PathTemplate($pushCredentialsFile);
		$this->pullCredentialsFile = new PathTemplate($pullCredentialsFile);
		$this->localSettings = new PathTemplate($localSettings);
		$this->kustomization = new PathTemplate($kustomization);
		$this->containerFile = new PathTemplate($containerFile);
	}

	public function set(string $name, string $value): void
	{
		$this->pushCredentialsFile->set($name, $value);
		$this->pullCredentialsFile->set($name, $value);
		$this->localSettings->set($name, $value);
		$this->kustomization->set($name, $value);
		$this->containerFile->set($name, $value);
	}
}
