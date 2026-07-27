<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Orchestrators\ConversationOrchestrator;
use App\AI\Services\AIConversationService;
use App\AI\Contracts\ConversationMemoryInterface;
use App\Services\ChatwootService;
use App\AI\Registry\ToolRegistry;
use App\DTO\ChatwootMessageDTO;
use App\Models\WhatsAppSession;
use App\AI\DTO\AIResponseDTO;
use App\AI\Contracts\ToolInterface;
use App\AI\Orders\OrderDraftManager;
use Mockery\MockInterface;
use Tests\TestCase;

class ConversationOrchestratorTest extends TestCase
{
    public function test_orchestrator_handles_conversational_flow_without_tools(): void
    {
        $messageDto = new ChatwootMessageDTO(
            conversationId: 123,
            phone: '59170012345',
            senderName: 'Juan Pérez',
            text: 'Hola',
            messageType: 'incoming',
            rawPayload: []
        );

        $session = new WhatsAppSession([
            'phone' => '59170012345',
            'name' => 'Juan Pérez',
            'step' => 'idle',
            'order_data' => [],
            'history' => []
        ]);

        $mockMemory = $this->mock(ConversationMemoryInterface::class, function (MockInterface $mock) use ($session) {
            $mock->shouldReceive('loadSession')
                ->once()
                ->with('59170012345', 'Juan Pérez')
                ->andReturn($session);

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'user', 'Hola');

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'assistant', 'Hola, ¿cómo estás?');

            $mock->shouldReceive('saveSession')
                ->once()
                ->with($session);
        });

        $mockAIService = $this->mock(AIConversationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generateReply')
                ->once()
                ->with(\Mockery::type('array'), \Mockery::type('array'))
                ->andReturn(new AIResponseDTO(reply: 'Hola, ¿cómo estás?'));
        });

        $mockChatwootService = $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with(123, 'Hola, ¿cómo estás?')
                ->andReturn(['id' => 999]);
        });

        $mockRegistry = $this->mock(ToolRegistry::class, function (MockInterface $mock) {
            $mock->shouldReceive('getToolsSchema')
                ->once()
                ->with('Hola', false)
                ->andReturn([]);
        });

        $mockDraft = (object)['items' => []];
        $mockDraftManager = $this->mock(OrderDraftManager::class, function (MockInterface $mock) use ($mockDraft) {
            $mock->shouldReceive('getDraft')
                ->andReturn($mockDraft);
        });

        $orchestrator = new ConversationOrchestrator($mockMemory, $mockAIService, $mockChatwootService, $mockRegistry, $mockDraftManager);
        $orchestrator->handle($messageDto);
    }

    public function test_orchestrator_handles_conversational_flow_with_tool_calling_loop(): void
    {
        $messageDto = new ChatwootMessageDTO(
            conversationId: 123,
            phone: '59170012345',
            senderName: 'Juan Pérez',
            text: '¿Qué tortas tienen?',
            messageType: 'incoming',
            rawPayload: []
        );

        $session = new WhatsAppSession([
            'phone' => '59170012345',
            'name' => 'Juan Pérez',
            'step' => 'idle',
            'order_data' => [],
            'history' => []
        ]);

        $mockMemory = $this->mock(ConversationMemoryInterface::class, function (MockInterface $mock) use ($session) {
            $mock->shouldReceive('loadSession')
                ->once()
                ->with('59170012345', 'Juan Pérez')
                ->andReturn($session);

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'user', '¿Qué tortas tienen?');

            $mock->shouldReceive('addMessageRaw')
                ->once()
                ->with($session, \Mockery::on(function ($msg) {
                    return $msg['role'] === 'assistant' && !empty($msg['tool_calls']);
                }));

            $mock->shouldReceive('addMessageRaw')
                ->once()
                ->with($session, \Mockery::on(function ($msg) {
                    return $msg['role'] === 'tool' && $msg['name'] === 'search_products' && $msg['content'] === 'Torta de chocolate';
                }));

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'assistant', 'Tenemos torta de chocolate.');

            $mock->shouldReceive('saveSession')
                ->once()
                ->with($session);
        });

        $mockAIService = $this->mock(AIConversationService::class, function (MockInterface $mock) {
            // First call returns a tool call
            $mock->shouldReceive('generateReply')
                ->once()
                ->with(\Mockery::type('array'), \Mockery::type('array'))
                ->andReturn(new AIResponseDTO(reply: null, toolCalls: [
                    [
                        'id' => 'call_123',
                        'function' => [
                            'name' => 'search_products',
                            'arguments' => '{"query":"torta"}'
                        ]
                    ]
                ]));

            // Second call returns final text response
            $mock->shouldReceive('generateReply')
                ->once()
                ->with(\Mockery::type('array'), \Mockery::type('array'))
                ->andReturn(new AIResponseDTO(reply: 'Tenemos torta de chocolate.'));
        });

        $mockChatwootService = $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with(123, 'Tenemos torta de chocolate.')
                ->andReturn(['id' => 999]);
        });

        $mockTool = $this->mock(ToolInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(['query' => 'torta'], \Mockery::any())
                ->andReturn('Torta de chocolate');
        });

        $mockRegistry = $this->mock(ToolRegistry::class, function (MockInterface $mock) use ($mockTool) {
            $mock->shouldReceive('getToolsSchema')
                ->once()
                ->with('¿Qué tortas tienen?', false)
                ->andReturn([['type' => 'function']]);

            $mock->shouldReceive('get')
                ->once()
                ->with('search_products')
                ->andReturn($mockTool);
        });

        $mockDraft = (object)['items' => []];
        $mockDraftManager = $this->mock(OrderDraftManager::class, function (MockInterface $mock) use ($mockDraft) {
            $mock->shouldReceive('getDraft')
                ->andReturn($mockDraft);
        });

        $orchestrator = new ConversationOrchestrator($mockMemory, $mockAIService, $mockChatwootService, $mockRegistry, $mockDraftManager);
        $orchestrator->handle($messageDto);
    }
}
