<?php

declare(strict_types=1);

namespace Operations;

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
            if (preg_match("~^COPY(?: --chown=\d+:\d+|--chmod=\d+)*((?: [A-Za-z_0-9/-]+)+) [A-Za-z_0-9/-]+$~", $line, $matches, ) === 1) {
                $sources = [$sources, ...explode(" ", trim($matches[1]))];
            }
        }

        return new Image(
            $this->registry,
            $this->project,
            $name,
            $containerFile,
            $dependencies,
            $sources,
            $rootImage,
            $requiresUpdate,
        );
    }
}
