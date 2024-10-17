<?php

declare(strict_types=1);

namespace GitOps\Service;

use GitOps\Model\CredentialsFile;
use GitOps\Model\GitOps;
use GitOps\Model\Image;
use GitOps\Model\LocalSettings;
use GitOps\Model\WorkingDirectory;
use GitOps\TopologicalDependencySorter;
use LogicException;

class WorkingDirectoryFactory
{
	private const HASH_ALGO = "sha256";

	public function __invoke(string $directory): WorkingDirectory
	{
		$directory = $this->findProjectRoot($directory);

		if (!file_exists($directory . "/" . GitOps::FILE_NAME)) {
			return new WorkingDirectory($directory);
		}

		$gitOps = (new Hydrator())->hydrateObject(
			model: GitOps::class,
			data: yaml_parse_file($directory . "/" . GitOps::FILE_NAME),
		);

		$gitOps->pathTemplates->set("root", $directory);
		$gitOps->registry->setPushCredentials(new CredentialsFile($gitOps->registry->host, $gitOps->pathTemplates->pushCredentialsFile->expand()));
		$gitOps->registry->setPullCredentials(new CredentialsFile($gitOps->registry->host, $gitOps->pathTemplates->pullCredentialsFile->expand()));

		$localSettingsPath = $gitOps->pathTemplates->localSettings->expand();
		if (!file_exists($localSettingsPath)) {
			return new WorkingDirectory($directory, $gitOps, null);
		}

		$localSettings = (new Hydrator())->hydrateObject(
			model: LocalSettings::class,
			data: yaml_parse_file($localSettingsPath),
		);

		return new WorkingDirectory($directory, $gitOps, $localSettings);
	}

	private function findProjectRoot(string $directory): string|null
	{
		$directory = realpath($directory);
		assert(is_string($directory), new LogicException("Path does not exists: $directory"));
		assert(is_dir($directory), new LogicException("Not a directory: $directory"));

		$dir = $directory;
		while (!file_exists($dir . "/" . GitOps::FILE_NAME)) {
			$dir = dirname($dir);
			if (strlen($dir) < 2) {
				return $directory;
			}
		}

		return $dir;
	}

	/**
	 * @return array<string,Image>
	 */
	private function getImages(GitOps $gitOps): array
	{
		$sorter = new TopologicalDependencySorter();
		$rootImage = null;

		foreach ($gitOps->images as $image) {
			$image->setContainerFile($gitOps->pathTemplates->containerFile->expand(["image" => $image->name]));
			$image->setRegistry($gitOps->registry);
			$this->parseContainerFile($image);
			if ($image->isRootImage) {
				if ($rootImage !== null) {
					throw new LogicException("Can't have multiple root images.");
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
			throw new LogicException("Root image is missing.");
		}

		$images = $sorter->sort();

		# Add root image as last
		$images[$rootImage->name] = $rootImage;

		return $images;
	}

	private function parseContainerFile(Image $image): void
	{
		$dependencies = [];
		$sources = [];

		foreach (file($image->containerFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
			if (preg_match("~^FROM $registry/$this->project/([A-Za-z][A-Za-z0-9]+):\\\$RING$~", $line, $matches) === 1) {
				$dependencies[] = $matches[1];
			}
			if (preg_match("~^COPY(?: --[A-Za-z-]+(?:=[^ ]*)?)*((?: [A-Za-z_0-9/-]+)+) [A-Za-z_0-9/-]+$~", $line, $matches,) === 1) {
				$sources = [$sources, ...explode(" ", trim($matches[1]))];
			}
		}
	}

	private function calculateRevisionHash(Image $image): string
	{
		$hashContext = hash_init(self::HASH_ALGO);

		foreach ($image->dependencies as $name => $value) {
			hash_update($hashContext, "$name:$value");
		}

		hash_update_file($hashContext, $image->containerFile);
		foreach ($image->sources as $source) {
			foreach ($this->iteratePath($source) as $file) {
				hash_update_file($hashContext, $file);
			}
		}

		return self::HASH_ALGO . ":" . base64_encode(hash_final($hashContext, true));
	}

	private function iteratePath(string $path): iterable
	{
		$path = realpath($path);
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
