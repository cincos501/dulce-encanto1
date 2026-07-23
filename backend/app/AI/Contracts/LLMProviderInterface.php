<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface LLMProviderInterface
{
    /**
     * Send messages to the LLM and retrieve the generated response.
     *
     * @param array<array{role: string, content: string}> $messages
     * @param array|null $tools
     * @return array{reply: ?string, tool_calls: ?array}
     */
    public function chat(array $messages, ?array $tools = null): array;
}
