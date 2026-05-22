<?php

namespace App\DTOs;

class ChatAnswerData
{
    public function __construct(
        public readonly string $answer,
        public readonly string $contextUsed,
        public readonly array $sources,
        public readonly array $chunks,
    ) {
    }

    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'context_used' => $this->contextUsed,
            'sources' => $this->sources,
            'chunks' => $this->chunks,
        ];
    }
}
