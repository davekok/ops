<?php

declare(strict_types=1);

namespace GitOps\Service;

use JsonException;
use LogicException;
use RuntimeException;

final readonly class Repository
{
    public const VERSION_PATTERN = "/^(\d+)\.(\d+)\.(\d+)$/";

    public function __construct(public string $name) {}

    public function list(): array
    {
        $data = ["tags" => [], "versions" => []];
        try {
            $tags = json_decode(`skopeo list-tags "docker://$this->name" 2>/dev/null` ?? '{"Tags":[]}', flags: JSON_THROW_ON_ERROR)->Tags ?? [];
        } catch (JsonException $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }
        foreach ($tags as $tag) {
            if (preg_match(self::VERSION_PATTERN, $tag)) {
                $data["versions"][] = $tag;
                continue;
            }
            $data["tags"][] = ["name" => $tag, "version" => $this->getTag($tag) ?? null];
        }
        usort($data["versions"], fn(string $versionA, string $versionB): int => $this->encodeVersion($versionA) - $this->encodeVersion($versionB));

        return $data;
    }

    public function get(string $tag, bool $verbose = false): string|array
    {
        if (!$verbose) {
            return $this->getTag($tag) ?? throw new LogicException("no version set for tag: $tag");
        }

        try {
            return json_decode(`skopeo inspect "docker://$this->name:$tag" 2>/dev/null`, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }
    }

    public function setTag(string $tag, string $version): void
    {
        `skopeo copy "docker://$this->name:$version" "docker://$this->name:$tag" 2>/dev/null`;
    }

    public function getTag(string $tag): string|null
    {
        try {
            if (preg_match(self::VERSION_PATTERN, $tag) === 1) {
                return $tag;
            }

            if ($tag === "latest") {
                return $this->getLatestVersion();
            }

            return json_decode(
                json: `skopeo inspect "docker://$this->name:$tag" 2>/dev/null` ?? '{"Labels":{"org.opencontainers.image.version":null}}',
                flags: JSON_THROW_ON_ERROR,
            )->Labels?->{"org.opencontainers.image.version"} ?? null;
        } catch (JsonException $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }
    }

    public function getLatestVersion(): string
    {
        try {
            $tags = json_decode(`skopeo list-tags "docker://$this->name" 2>/dev/null` ?? '{"Tags":[]}', flags: JSON_THROW_ON_ERROR)->Tags ?? [];
        } catch (JsonException $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }
        $latestMajor = 0;
        $latestMinor = 0;
        $latestPatch = 0;
        foreach ($tags as $tag) {
            if (preg_match(self::VERSION_PATTERN, $tag, $matches) === 1) {
                $major = (int)$matches[1];
                $minor = (int)$matches[2];
                $patch = (int)$matches[3];
                if (
                    $major > $latestMajor
                    || ($major === $latestMajor && $minor > $latestMinor)
                    || ($major === $latestMajor && $minor === $latestMinor && $patch > $latestPatch)
                ) {
                    $latestMajor = $major;
                    $latestMinor = $minor;
                    $latestPatch = $patch;
                }
            }
        }

        return "$latestMajor.$latestMinor.$latestPatch";
    }

    /**
     * Bump the version number.
     *
     * If previousTag is null, bump the major part.
     * If previousTag is set, get the version of it. And if major and minor are the same, bump minor part otherwise patch part.
     */
    public function bumpVersion(string|null $version, string|null $previousTag): string
    {
        if ($version === null) {
            return "0.0.0";
        }

        $version = explode(".", $version);

        if ($previousTag === null) {
            ++$version[0];
            $version[1] = 0;
            $version[2] = 0;
            return implode(".", $version);
        }

        $previousVersion = $this->getTag($previousTag);
        if ($previousVersion !== null) {
            $previousVersion = explode(".", $previousVersion);
            if ($version[0] === $previousVersion[0] && $version[1] === $previousVersion[1]) {
                ++$version[1];
                $version[2] = 0;

                return implode(".", $version);
            }
        }

        ++$version[2];

        return implode(".", $version);
    }

    public function __toString(): string
    {
        return $this->name;
    }

    private function encodeVersion(string $version): int
    {
        [$major, $minor, $patch] = explode(".", $version);

        return (int)$major << 43 | (int)$minor << 22 | (int)$patch;
    }
}
