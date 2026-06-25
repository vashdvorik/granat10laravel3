<?php

namespace App\Data;

class ModerationImage
{
    public function __construct(
        public readonly string $mimeType,
        public readonly string $base64Data,
    ) {}
}
