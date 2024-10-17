<?php

declare(strict_types=1);

namespace GitOps\Model;

use GitOps\Attribute\ArrayType;
use LogicException;
use ReflectionClass;

final readonly class GitOps
{
	public const FILE_NAME = "gitops.yaml";
	public const API_VERSIONS = [
		"gitops.davekok.nl/v1alpha1",
	];

	public function __construct(
		public string $apiVersion,
		public string $kind,
		public MetaData $metadata,
		public PathTemplates $pathTemplates,
		public Registry $registry,
		/** @var list<string> $rings */ #[ArrayType("string")] public array $rings,
		/** @var list<string> $environments */ #[ArrayType("string")] public array $environments,
		/** @var array<string,string> $commonImageLabels */ #[ArrayType("string", map: true)] public array $commonImageLabels,
		/** @var list<Image> $images */ #[ArrayType(Image::class)] public array $images,
		/** @var list<Site> $sites */ #[ArrayType(Site::class)] public array $sites,
	) {
		$shortName = (new ReflectionClass($this))->getShortName();
        assert(in_array($this->apiVersion, self::API_VERSIONS), new LogicException("Unsupported API $this->apiVersion."));
        assert($shortName === $this->kind, new LogicException("Expected a $shortName document."));
	}
}
