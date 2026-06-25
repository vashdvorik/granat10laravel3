<?php

namespace App\Data;

class ModerationProviderMetadata
{
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly string $promptVersion,
        public readonly string $policyVersion,
    ) {}
}
