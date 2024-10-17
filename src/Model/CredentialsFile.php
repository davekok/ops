<?php

declare(strict_types=1);

namespace GitOps\Model;

use AssertionError;
use JsonException;
use RuntimeException;

final class CredentialsFile
{
    public string|null $username = null;
    public string|null $password = null;

    public function __construct(
        public string $host,
        public string $path,
    ) {
        if (file_exists($this->path)) {
			$this->read();
		}
    }

    public function read(): self
    {
        try {
            $contents = file_get_contents($this->path);
            assert(is_string($contents), new AssertionError("Unable to read credentials file: $this->path"));
            $auths = json_decode($contents, associative: true, flags: JSON_THROW_ON_ERROR);
            [$this->username, $this->password] = explode(":", base64_decode($auths["auths"][$this->host]["auth"]));

            return $this;
        } catch (JsonException $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }
    }

    public function write(): self
    {
        try {
            $auths = [
                "auths" => [
                    $this->host => [
                        "auth" => base64_encode("$this->username:$this->password"),
                    ]
                ]
            ];

            $contents = json_encode($auths, flags: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            file_put_contents($this->path, $contents);

            return $this;
        } catch (JsonException $exception) {
            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }
    }
}
