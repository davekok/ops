<?php

declare(strict_types=1);

namespace GitOps;

use function str_starts_with;

readonly class ContainerFileParser
{
    public function __construct(
        private string $registry,
        private string $project,
    ) {}

    public function parse(string $containerFile): Image
    {
        $name = basename($containerFile, ".containerfile");
        $dependencies = [];
        $sources = [];
        $rootImage = false;
        $requiresUpdate = false;
        $checks = [];
        foreach (file($containerFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '# ROOT IMAGE') {
                $rootImage = true;
            }
            if ($line === '# REQUIRE UPDATE') {
                $requiresUpdate = true;
            }
            if (preg_match("~^FROM $this->registry/$this->project/([A-Za-z][A-Za-z0-9]+):\\\$RING$~", $line, $matches) === 1) {
                $dependencies[] = $matches[1];
            }
            if (preg_match("~^COPY(?: --[A-Za-z-]+(?:=[^ ]*)?)*((?: [A-Za-z_0-9/-]+)+) [A-Za-z_0-9/-]+$~", $line, $matches, ) === 1) {
                $sources = [$sources, ...explode(" ", trim($matches[1]))];
            }
            if (preg_match("~^# CHECK ([A-Za-z0-9-]+): (.*)$~", $line, $matches) === 1) {
                $checks[$matches[1]] = $matches[2];
            }
        }

        return new Image(
            registry: $this->registry,
            project: $this->project,
            name: $name,
            containerFile: $containerFile,
            dependencies: $dependencies,
            sources: $sources,
            rootImage: $rootImage,
            requiresUpdate: $requiresUpdate,
            checks: $checks,
        );
    }
}
