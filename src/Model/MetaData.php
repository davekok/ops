<?php

declare(strict_types=1);

namespace GitOps\Model;

readonly class MetaData
{
    public function __construct(
        public string $name,
    ) {}
}
