<?php

namespace App\DTOs;

class ChunkData
{
    public function __construct(
        public readonly int $index,
        public readonly string $content,
        public readonly array $metadata = [],
    ) {
    }
}
