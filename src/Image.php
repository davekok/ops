<?php

declare(strict_types=1);

namespace GitOps;

class Image
{
    private const LABELS = [
        "org.opencontainers.image.version",
        "org.opencontainers.image.revision",
        "org.opencontainers.image.ref.name",
        "org.opencontainers.image.vendor",
    ];
    private const DEPENDENCY_LABEL_PREFIX = "dependency.";

    private array $labels = [];

    public function __construct(
        public readonly string $registry,
        public readonly string $project,
        public readonly string $name,
        public readonly string $containerFile,
        public readonly array $dependencies,
        public readonly array $sources,
        public readonly bool $rootImage,
        public readonly bool $requiresUpdate,
        public readonly array $checks,
    ) {}

    public function setLabelsFromMetaData(array $metaData): void
    {
        foreach (self::LABELS as $label) {
            if (isset($metaData["Labels"][$label])) {
                $this->labels[$label] = $metaData["Labels"][$label];
                continue;
            }
            if (str_starts_with($label, self::DEPENDENCY_LABEL_PREFIX) && isset($this->dependencies[substr($label, strlen(self::DEPENDENCY_LABEL_PREFIX))])) {
                $this->labels[$label] = $metaData["Labels"][$label];
            }
        }
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function setVersion(string $version): void
    {
        $this->labels["org.opencontainers.image.version"] = $version;
    }

    public function getVersion(): string
    {
        return $this->labels["org.opencontainers.image.version"];
    }

    public function setDependencyVersion(string $name, string $version): void
    {
        $this->labels[self::DEPENDENCY_LABEL_PREFIX . $name] = $version;
    }

    public function getDependencyVersion(string $name): string
    {
        return $this->labels[self::DEPENDENCY_LABEL_PREFIX . $name];
    }

    public function getDependencies(): iterable
    {
        foreach ($this->labels as $key => $value) {
            if (str_starts_with($key, self::DEPENDENCY_LABEL_PREFIX)) {
                yield substr($key, strlen(self::DEPENDENCY_LABEL_PREFIX)) => $value;
            }
        }
    }

    public function setRevisionHash(string $hash): void
    {
        $this->labels["org.opencontainers.image.revision"] = $hash;
    }

    public function getRevisionHash(): string
    {
        return $this->labels["org.opencontainers.image.revision"];
    }

    public function setReferenceName(string $refname): void
    {
        $this->labels["org.opencontainers.image.ref.name"] = $refname;
    }

    public function getReferenceName(): string
    {
        return $this->labels["org.opencontainers.image.ref.name"];
    }

    public function setVendor(string $vendor): void
    {
        $this->labels["org.opencontainers.image.vendor"] = $vendor;
    }

    public function getVendor(): string
    {
        return $this->labels["org.opencontainers.image.vendor"];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
