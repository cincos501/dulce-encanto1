<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Contracts\ConversationMemoryInterface;
use App\AI\Contracts\ToolInterface;
use App\AI\DTO\AIResponseDTO;
use App\AI\Orchestrators\ConversationOrchestrator;
use App\AI\Orders\OrderDraftManager;
use App\AI\Registry\ToolRegistry;
use App\AI\Services\AIConversationService;
use App\DTO\ChatwootMessageDTO;
use App\Models\WhatsAppSession;
use App\Services\ChatwootService;
use Mockery\MockInterface;
use Tests\TestCase;

class AIVariantSelectionSecurityTest extends TestCase
{
    /**
     * Caso 1: Cliente pregunta por un producto.
     * Resultado esperado: Puede usar search_products/search_variants. No tiene disponible get_variant_extras.
     */
    public function test_case_1_ask_for_product_does_not_include_get_variant_extras(): void
    {
        $registry = $this->app->make(ToolRegistry::class);

        // User history contains only asking for a product
        $history = [
            ['role' => 'user', 'content' => 'Hola, quiero una torta tres leches'],
        ];

        $schema = $registry->getToolsSchema($history, false);
        $toolNames = $registry->getLastToolNames();

        $this->assertContains('search_products', $toolNames);
        $this->assertContains('search_variants', $toolNames);
        $this->assertNotContains('get_variant_extras', $toolNames);
    }

    /**
     * Caso 2: Cliente intenta pedir extras sin seleccionar variante.
     * Resultado esperado: get_variant_extras es bloqueado.
     */
    public function test_case_2_ask_for_extras_without_variant_is_blocked_by_orchestrator(): void
    {
        $messageDto = new ChatwootMessageDTO(
            conversationId: 123,
            phone: '59170012345',
            senderName: 'Juan Pérez',
            text: '¿Qué adicionales tiene?',
            messageType: 'incoming',
            rawPayload: []
        );

        // History includes the search_variants tool output but NO user message selecting a variant after it
        $session = new WhatsAppSession([
            'phone' => '59170012345',
            'name' => 'Juan Pérez',
            'step' => 'idle',
            'order_data' => [],
            'history' => [
                ['role' => 'user', 'content' => 'Hola, quiero una torta tres leches'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [
                    [
                        'id' => 'call_sv_123',
                        'type' => 'function',
                        'function' => ['name' => 'search_variants', 'arguments' => '{"product_id":5}'],
                    ],
                ]],
                ['role' => 'tool', 'name' => 'search_variants', 'tool_call_id' => 'call_sv_123', 'content' => '- [ID Variante: 10] Torta Tres Leches - Presentación: Mediana'],
            ],
        ]);

        $mockMemory = $this->mock(ConversationMemoryInterface::class, function (MockInterface $mock) use ($session) {
            $mock->shouldReceive('loadSession')
                ->once()
                ->andReturn($session);

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'user', '¿Qué adicionales tiene?');

            // Expect to save raw tool response showing the security flow error
            $mock->shouldReceive('addMessageRaw')
                ->once()
                ->with($session, \Mockery::on(function ($msg) {
                    return $msg['role'] === 'assistant' && ! empty($msg['tool_calls']);
                }));

            $mock->shouldReceive('addMessageRaw')
                ->once()
                ->with($session, \Mockery::on(function ($msg) {
                    return $msg['role'] === 'tool' &&
                           $msg['name'] === 'get_variant_extras' &&
                           str_contains($msg['content'], 'ERROR DE FLUJO');
                }));

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'assistant', 'Selecciona primero una variante por favor.');

            $mock->shouldReceive('saveSession')
                ->once()
                ->with($session);
        });

        $mockAIService = $this->mock(AIConversationService::class, function (MockInterface $mock) {
            // First call decides to call get_variant_extras
            $mock->shouldReceive('generateReply')
                ->once()
                ->andReturn(new AIResponseDTO(reply: null, toolCalls: [
                    [
                        'id' => 'call_gve_123',
                        'function' => [
                            'name' => 'get_variant_extras',
                            'arguments' => '{"variant_id":10}',
                        ],
                    ],
                ]));

            // Second call after interceptor returns the textual instructions to the user
            $mock->shouldReceive('generateReply')
                ->once()
                ->andReturn(new AIResponseDTO(reply: 'Selecciona primero una variante por favor.'));
        });

        $mockChatwootService = $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldReceive('toggleTypingStatus')->zeroOrMoreTimes();
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with(123, 'Selecciona primero una variante por favor.');
        });

        // Use real registry but mock the variant extras tool call execution
        $registry = $this->app->make(ToolRegistry::class);

        $mockDraft = (object) ['items' => []];
        $mockDraftManager = $this->mock(OrderDraftManager::class, function (MockInterface $mock) use ($mockDraft) {
            $mock->shouldReceive('getDraft')->andReturn($mockDraft);
        });

        $orchestrator = new ConversationOrchestrator(
            $mockMemory,
            $mockAIService,
            $mockChatwootService,
            $registry,
            $mockDraftManager
        );

        $orchestrator->handle($messageDto);
    }

    /**
     * Caso 3: Cliente selecciona una variante.
     * Resultado esperado: get_variant_extras funciona correctamente.
     */
    public function test_case_3_ask_for_extras_with_variant_selected_executes_successfully(): void
    {
        $messageDto = new ChatwootMessageDTO(
            conversationId: 123,
            phone: '59170012345',
            senderName: 'Juan Pérez',
            text: '¿Qué adicionales tiene?',
            messageType: 'incoming',
            rawPayload: []
        );

        // History includes search_variants, AND a user message selecting the variant
        $session = new WhatsAppSession([
            'phone' => '59170012345',
            'name' => 'Juan Pérez',
            'step' => 'idle',
            'order_data' => [],
            'history' => [
                ['role' => 'user', 'content' => 'Hola, quiero una torta tres leches'],
                ['role' => 'assistant', 'content' => '', 'tool_calls' => [
                    [
                        'id' => 'call_sv_123',
                        'type' => 'function',
                        'function' => ['name' => 'search_variants', 'arguments' => '{"product_id":5}'],
                    ],
                ]],
                ['role' => 'tool', 'name' => 'search_variants', 'tool_call_id' => 'call_sv_123', 'content' => '- [ID Variante: 10] Torta Tres Leches - Presentación: Mediana'],
                ['role' => 'user', 'content' => 'Quiero la mediana'], // User selected it!
            ],
        ]);

        $mockMemory = $this->mock(ConversationMemoryInterface::class, function (MockInterface $mock) use ($session) {
            $mock->shouldReceive('loadSession')
                ->once()
                ->andReturn($session);

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'user', '¿Qué adicionales tiene?');

            $mock->shouldReceive('addMessageRaw')
                ->once()
                ->with($session, \Mockery::on(function ($msg) {
                    return $msg['role'] === 'assistant' && ! empty($msg['tool_calls']);
                }));

            // Expect to save raw tool response with SUCCESS context (no error)
            $mock->shouldReceive('addMessageRaw')
                ->once()
                ->with($session, \Mockery::on(function ($msg) {
                    return $msg['role'] === 'tool' &&
                           $msg['name'] === 'get_variant_extras' &&
                           ! str_contains($msg['content'], 'ERROR DE FLUJO');
                }));

            $mock->shouldReceive('addMessage')
                ->once()
                ->with($session, 'assistant', 'Tiene chispas.');

            $mock->shouldReceive('saveSession')
                ->once()
                ->with($session);
        });

        $mockAIService = $this->mock(AIConversationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('generateReply')
                ->once()
                ->andReturn(new AIResponseDTO(reply: null, toolCalls: [
                    [
                        'id' => 'call_gve_123',
                        'function' => [
                            'name' => 'get_variant_extras',
                            'arguments' => '{"variant_id":10}',
                        ],
                    ],
                ]));

            $mock->shouldReceive('generateReply')
                ->once()
                ->andReturn(new AIResponseDTO(reply: 'Tiene chispas.'));
        });

        $mockChatwootService = $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldReceive('toggleTypingStatus')->zeroOrMoreTimes();
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with(123, 'Tiene chispas.');
        });

        // Mock the get_variant_extras tool response
        $mockTool = $this->mock(ToolInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getName')->andReturn('get_variant_extras');
            $mock->shouldReceive('getDescription')->andReturn('Desc');
            $mock->shouldReceive('getParameters')->andReturn([]);
            $mock->shouldReceive('execute')
                ->once()
                ->with(['variant_id' => 10], \Mockery::any())
                ->andReturn('{"success":true,"extras":[]}');
        });

        // Make real ToolRegistry but replace get_variant_extras with mock
        $registry = $this->app->make(ToolRegistry::class);
        $registry->register($mockTool);

        $mockDraft = (object) ['items' => []];
        $mockDraftManager = $this->mock(OrderDraftManager::class, function (MockInterface $mock) use ($mockDraft) {
            $mock->shouldReceive('getDraft')->andReturn($mockDraft);
        });

        $orchestrator = new ConversationOrchestrator(
            $mockMemory,
            $mockAIService,
            $mockChatwootService,
            $registry,
            $mockDraftManager
        );

        $orchestrator->handle($messageDto);
    }
}
