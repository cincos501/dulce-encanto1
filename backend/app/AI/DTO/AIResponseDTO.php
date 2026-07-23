<?php

declare(strict_types=1);

namespace App\AI\DTO;

class AIResponseDTO
{
    public function __construct(
        public readonly ?string $reply,
        public readonly ?array $toolCalls = null,
        public readonly array $rawResponse = []
    ) {}
}
