<?php

declare(strict_types=1);

namespace GitOps\Service;

use function base64_encode;
use function json_encode;
use function strtolower;
use function yaml_emit;

readonly class Site
{
    public string $agentName;

    public function __construct(
        public string $namespace,
        public string $registry,
        public string $username,
        public string $password,
        public string $project,
        public string $agentImage,
        public string $agentImageVersion,
        public string $kustomization,
        public string $ring,
        public string $probeInterval,
    )
    {
        $this->agentName = strtolower(__NAMESPACE__) . "-agent";
    }

    public function generateYaml(): string
    {
        return
            yaml_emit($this->namespace())
            . yaml_emit($this->registrySecret())
            . yaml_emit($this->serviceAccount())
            . yaml_emit($this->roleBinding())
            . yaml_emit($this->deployment());
    }

    public function namespace(): array
    {
        return [
            "apiVersion" => "v1",
            "kind" => "Namespace",
            "metadata" => [
                "name" => $this->namespace,
                "labels" => [
                    "app.kubernetes.io/name" => $this->namespace,
                ],
            ],
        ];
    }

    public function registrySecret(): array
    {
        return [
            "apiVersion" => "v1",
            "kind" => "Secret",
            "metadata" => [
                "name" => $this->registry,
                "namespace" => $this->namespace,
            ],
            "type" => "kubernetes.io/dockerconfigjson",
            "data" => [
                ".dockerconfigjson" => base64_encode(json_encode(
                    value: ["auths" => [$this->registry => ["username" => $this->username, "password" => $this->password, "auth" => base64_encode("$this->username:$this->password")]]],
                    flags: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                )),
            ],
        ];
    }


    public function serviceAccount(): array
    {
        return [
            "apiVersion" => "v1",
            "kind" => "ServiceAccount",
            "metadata" => [
                "name" => $this->agentName,
                "namespace" => $this->namespace,
                "labels" => [
                    "app.kubernetes.io/name" => "$this->agentName",
                ],
            ],
            "imagePullSecrets" => [
                ["name" => $this->registry],
            ],
        ];
    }

    public function roleBinding(): array
    {
        return [
            "apiVersion" => "rbac.authorization.k8s.io/v1",
            "kind" => "RoleBinding",
            "metadata" => [
                "name" => "$this->agentName-$this->namespace",
                "namespace" => $this->namespace,
                "labels" => [
                    "app.kubernetes.io/name" => "$this->agentName-$this->namespace",
                ],
            ],
            "roleRef" => [
                "apiGroup" => "rbac.authorization.k8s.io",
                "kind" => "ClusterRole",
                "name" => "cluster-admin",
                "subjects" => [
                    [
                        "namespace" => $this->namespace,
                        "kind" => "ServiceAccount",
                        "name" => $this->agentName,
                    ],
                ],
            ],
        ];
    }

    public function deployment(): array
    {
        return [
            "apiVersion" => "apps/v1",
            "kind" => "Deployment",
            "metadata" => [
                "name" => $this->agentName,
                "namespace" => $this->namespace,
                "labels" => [
                    "app.kubernetes.io/name" => "$this->agentName",
                ],
            ],
            "spec" => [
                "replicas" => 1,
                "selector" => [
                    "matchLabels" => [
                        "app.kubernetes.io/name" => $this->agentName,
                    ],
                ],
                "template" => [
                    "spec" => [
                        "automountServiceAccountToken" => true,
                        "serviceAccountName" => $this->agentName,
                        "containers" => [
                            [
                                "name" => $this->agentName,
                                "image" => "$this->agentImage:$this->agentImageVersion",
                                "imagePullPolicy" => "IfNotPresent",
                                "securityContext" => [
                                    "runAsUser" => 1000,
                                    "runAsGroup" => 1000,
                                    "runAsNonRoot" => true,
                                    "readOnlyRootFilesystem" => true,
                                    "allowPrivilegeEscalation" => false,
                                    "privileged" => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
