<?php

declare(strict_types=1);

namespace Operations;

use Exception;
use RuntimeException;

readonly class Etcetera
{
    public function __construct(
        private CurrentDirectory $currentDir,
        private string $registry,
        private string $project,
    ) {}

    public function getKustomizations(): iterable
    {
        yield from glob("$this/*/kustomization.yaml");
        yield from glob("$this/*/*/kustomization.yaml");
    }

    public function getImage(string $name): Image
    {
        return $this->getImages($name)[$name];
    }

    /**
     * @return array<string,Image>
     */
    public function getImages(string ...$filter): array
    {
        $sorter = new TopologicalDependencySorter();
        $sortLastImage = null;

        foreach (glob("$this/*.containerfile") as $containerFile) {
            $name = basename($containerFile, ".containerfile");
            if (count($filter) > 0 && !in_array($name, $filter, true)) {
                continue;
            }
            $dependencies = [];
            $sortLast = false;
            $requiresUpdate = false;
            foreach (file($containerFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line === '# SORT LAST') {
                    if ($sortLastImage !== null) {
                        throw new RuntimeException("Can't have multiple containers sorted as last.");
                    }
                    $sortLast = true;
                }
                if ($line === '# REQUIRE UPDATE') {
                    $requiresUpdate = true;
                }
                if (preg_match("/^FROM $this->registry\/$this->project\/([A-Za-z][A-Za-z0-9]+):\\\$RING$/", $line, $matches) === 1) {
                    $dependencies[] = $matches[1];
                }
            }

            $image = new Image($this->registry, $this->project, $name, $containerFile, $sortLast, $requiresUpdate);
            if ($sortLast) {
                $sortLastImage = $image;
                continue;
            }

            $sorter->addNode($name, $image);
            foreach ($dependencies as $dependency) {
                $sorter->addDependency($name, $dependency);
            }
        }

        $images = $sorter->sort();

        if ($sortLastImage !== null) {
            $images[$sortLastImage->name] = $sortLastImage;
        }

        return $images;
    }

    public function __toString(): string
    {
        return "$this->currentDir/etc";
    }
}
