<?php

declare(strict_types=1);

namespace GitOps;

use GitOps\Executor\CurrentDirectory;
use LogicException;
use RuntimeException;

readonly class WorkingCopy
{
    private const HASH_ALGO = "sha256";

    public function __construct(
        private CurrentDirectory $currentDir,
        private ContainerFileParser $parser,
        private string $etcDir = "etc",
    ) {}

    public function getKustomizations(): iterable
    {
        yield from glob("$this/$this->etcDir/*/kustomization.yaml");
        yield from glob("$this/$this->etcDir/*/*/kustomization.yaml");
    }

    /**
     * @return array<string,Image>
     */
    public function getImages(): array
    {
        $sorter = new TopologicalDependencySorter();
        $rootImage = null;

        foreach (glob("$this/$this->etcDir/*.containerfile") as $containerFile) {
            $image = $this->parser->parse($containerFile);
            if ($image->rootImage) {
                if ($rootImage !== null) {
                    throw new RuntimeException("Can't have multiple root images.");
                }
                $rootImage = $image;
                continue;
            }

            $sorter->addNode($image->name, $image);
            foreach ($image->dependencies as $dependency) {
                $sorter->addDependency($image->name, $dependency);
            }
        }
        if ($rootImage === null) {
            throw new RuntimeException("Root image is missing.");
        }

        $images = $sorter->sort();

        # Add root image as last
        $images[$rootImage->name] = $rootImage;

        return $images;
    }

    public function calculateRevisionHash(Image $image): string
    {
        $hashContext = hash_init(self::HASH_ALGO);

        hash_update_file($hashContext, $image->containerFile);
        foreach ($image->sources as $source) {
            foreach ($this->iteratePath($source) as $file) {
                hash_update_file($hashContext, $file);
            }
        }

        return self::HASH_ALGO . ":" . base64_encode(hash_final($hashContext, true));
    }

    public function __toString(): string
    {
        return (string)$this->currentDir;
    }

    private function iteratePath(string $path): iterable
    {
        $path = realpath("$this->currentDir/$path");
        if (!$path) {
            throw new LogicException("Expected path $path to exist.");
        }
        if (is_file($path)) {
            yield $path;
            return;
        }

        $dirEntries = [];
        $fileEntries = [];

        $dir = opendir($path);
        while (($entry = readdir($dir)) !== false) {
            if ($entry === "." || $entry === "..") {
                continue;
            }
            $entry = "$path/$entry";
            if (is_dir($entry)) {
                $dirEntries[] = $entry;
            }
            $fileEntries[] = $entry;
        }
        closedir($dir);

        sort($fileEntries);
        yield from $fileEntries;
        unset($fileEntries);

        sort($dirEntries);
        foreach ($dirEntries as $dirEntry) {
            yield from $this->iteratePath($dirEntry);
        }
    }
}
