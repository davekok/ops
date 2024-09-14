<?php

declare(strict_types=1);

namespace GitOps;

enum LocationLevel: string
{
    case KUBERNETES = "kubernetes";
    case PROJECT = "project";
    case USER = "user";
    case SYSTEM = "system";

    public function path(string $project): string
    {
        return match ($this) {
            self::KUBERNETES => "/run/secrets/registry-secret",
            self::PROJECT => "$project/.gitops-registry-secret",
            self::USER => getenv("HOME") . "/.local/gitops/registry-secret",
            self::SYSTEM => "/etc/gitops/registry-secret",
        };
    }
}
