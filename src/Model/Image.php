<?php

declare(strict_types=1);

namespace GitOps\Model;

use GitOps\Attribute\ArrayType;

readonly class Image
{
    private const COMPUTED_LABELS = [
        "org.opencontainers.image.version",
        "org.opencontainers.image.revision",
        "org.opencontainers.image.ref.name",
    ];
    private const DEPENDENCY_LABEL_PREFIX = "dependency.";

    private array $commonLabels;

    public Registry $registry;
	public string $containerFile;
	public array $dependencies;
	public array $sources;
	public string $version;
	public string $revisionHash;

    public function __construct(
        public string $name = "example",
        public bool $isRootImage = false,
        public bool $requiresUpdate = false,
        #[ArrayType("string")] public array $checks = [],
    ) {}

	public function setContainerFile(string $containerFile): void
	{
		$this->containerFile = $containerFile;
	}

	public function setRegistry(Registry $registry): void
	{
		$this->registry = $registry;
	}

    public function setCommonLabels(array $labels): void
    {
        $this->commonLabels = $labels;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }

    public function setRevisionHash(string $revisionHash): void
    {
        $this->revisionHash = $revisionHash;
    }

    public function getLabels(): array
    {
		$dependencyLabels = [];
		foreach ($this->dependencies as $name => $version) {
			$dependencyLabels[self::DEPENDENCY_LABEL_PREFIX . $name] = $version;
		}

        return [
			...$this->commonLabels,
			...$dependencyLabels,
			"org.opencontainers.image.ref.name" => $this->name,
			"org.opencontainers.image.version" => $this->version,
			"org.opencontainers.image.revision" => $this->revisionHash,
		];
    }
}
