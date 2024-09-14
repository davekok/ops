<?php

declare(strict_types=1);

namespace GitOps;

use function base64_encode;
use function json_encode;

class Manifest
{
    public function registrySecret(string $registry, string $username, string $password): string
    {
        return yaml_emit([
            "apiVersion" => "v1",
            "kind" => "Secret",
            "metadata" => [
                "name" => $registry,
            ],
            "type" => "kubernetes.io/dockerconfigjson",
            "data" => [
                ".dockerconfigjson" => base64_encode(json_encode(
                    value: ["auths" => [$registry => ["username" => $username, "password" => $password, "auth" => base64_encode("$username:$password")]]],
                    flags: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                ))
            ]
        ]);
    }
}
