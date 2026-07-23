<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Contracts\LLMProviderInterface;
use App\AI\Services\AIConversationService;
use Mockery\MockInterface;
use Tests\TestCase;

class AIConversationServiceTest extends TestCase
{
    public function test_ai_conversation_service_builds_prompt_and_calls_llm(): void
    {
        $history = [
            ['role' => 'user', 'content' => 'Hola'],
            ['role' => 'assistant', 'content' => 'Hola, ¿cómo puedo ayudarte hoy?'],
            ['role' => 'user', 'content' => '¿Tiene torta de chocolate?']
        ];

        $mockLLM = $this->mock(LLMProviderInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->with(\Mockery::on(function ($messages) {
                    // System prompt + 3 history = 4 items
                    return count($messages) === 4
                        && $messages[0]['role'] === 'system'
                        && str_contains($messages[0]['content'], 'Dulce Encanto')
                        && $messages[1]['content'] === 'Hola'
                        && $messages[3]['content'] === '¿Tiene torta de chocolate?';
                }), \Mockery::any())
                ->andReturn([
                    'reply' => 'Sí, tenemos deliciosa torta de chocolate a Bs. 150.',
                    'tool_calls' => null
                ]);
        });

        $service = new AIConversationService($mockLLM);
        $response = $service->generateReply($history);

        $this->assertEquals('Sí, tenemos deliciosa torta de chocolate a Bs. 150.', $response->reply);
    }
}
