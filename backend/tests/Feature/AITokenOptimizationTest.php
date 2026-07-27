<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Services\AIConversationService;
use App\AI\Registry\ToolRegistry;
use App\AI\Orders\OrderDraftManager;
use App\Models\WhatsAppSession;
use App\AI\Memory\RedisConversationMemory;
use App\Repositories\WhatsAppSessionRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AITokenOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected RedisConversationMemory $memory;
    protected OrderDraftManager $draftManager;
    protected WhatsAppSessionRepositoryInterface $sessionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        // Bind an in-memory session repository to isolate test from Redis dependencies
        $this->app->singleton(WhatsAppSessionRepositoryInterface::class, function () {
            return new class implements WhatsAppSessionRepositoryInterface {
                protected array $store = [];

                public function get(string $phone): ?array
                {
                    return $this->store[$phone] ?? null;
                }

                public function set(string $phone, array $data, int $ttl = 3600): void
                {
                    $this->store[$phone] = $data;
                }

                public function delete(string $phone): void
                {
                    unset($this->store[$phone]);
                }
            };
        });
        $this->sessionRepository = $this->app->make(WhatsAppSessionRepositoryInterface::class);
        $this->memory = new RedisConversationMemory($this->sessionRepository);
        $this->draftManager = new OrderDraftManager($this->sessionRepository);
    }

    /**
     * Caso 1 & 3: Pruebas de poda de historial.
     * Genera una conversación de 20 mensajes y verifica que al podar se inicie en 'user' y se acote.
     */
    public function test_conversation_history_pruning_bounds_correctly_and_starts_on_user(): void
    {
        $history = [];
        for ($i = 1; $i <= 10; $i++) {
            $history[] = ['role' => 'user', 'content' => "Mensaje de usuario {$i}"];
            // Add a tool call block to test safety
            $history[] = [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => [
                    [
                        'id' => "call_{$i}",
                        'type' => 'function',
                        'function' => ['name' => 'search_products', 'arguments' => '{"query":"torta"}']
                    ]
                ]
            ];
            $history[] = [
                'role' => 'tool',
                'name' => 'search_products',
                'tool_call_id' => "call_{$i}",
                'content' => 'Resultado de torta'
            ];
            $history[] = ['role' => 'assistant', 'content' => "Respuesta {$i}"];
        }

        // Total of 40 messages in history
        $this->assertEquals(40, count($history));

        // Prune to max 10
        $pruned = AIConversationService::pruneHistory($history, 10);

        // Verify the count is <= 10 (and starts on a user message)
        $this->assertTrue(count($pruned) <= 10);
        $this->assertEquals('user', $pruned[0]['role']);

        // Check that any tool message in the slice has its preceding assistant message
        foreach ($pruned as $index => $msg) {
            if ($msg['role'] === 'tool') {
                $toolCallId = $msg['tool_call_id'];
                // Search backwards for the assistant tool_calls message
                $foundAssistant = false;
                for ($j = $index - 1; $j >= 0; $j--) {
                    if ($pruned[$j]['role'] === 'assistant' && !empty($pruned[$j]['tool_calls'])) {
                        foreach ($pruned[$j]['tool_calls'] as $tc) {
                            if ($tc['id'] === $toolCallId) {
                                $foundAssistant = true;
                                break 2;
                            }
                        }
                    }
                }
                $this->assertTrue($foundAssistant, "Encontrado mensaje de tool huérfano sin su llamada asistente previa.");
            }
        }
    }

    /**
     * Caso 2: El borrador del pedido se conserva íntegro en Redis aunque el historial sea acotado.
     */
    public function test_order_draft_is_preserved_independent_of_history_limits(): void
    {
        $phone = '59170012345';
        
        // Add item to draft
        $this->draftManager->addItem(
            phone: $phone,
            productId: 5,
            productName: 'Torta Tres Leches',
            variantId: 10,
            variantName: 'Mediana',
            quantity: 2,
            unitPrice: 150.00
        );

        // Load session and add 20 messages to conversation history
        $session = $this->memory->loadSession($phone, 'Juan');
        for ($i = 0; $i < 20; $i++) {
            $this->memory->addMessage($session, 'user', "Mensaje {$i}");
            $this->memory->addMessage($session, 'assistant', "Respuesta {$i}");
        }
        $this->memory->saveSession($session);

        // Reload session and verify history is bounded (Redis limit is now 12)
        $reloadedSession = $this->memory->loadSession($phone, 'Juan');
        $this->assertEquals(12, count($reloadedSession->history));

        // Verify draft is STILL complete in Redis and untouched!
        $draft = $this->draftManager->getDraft($phone);
        $this->assertNotEmpty($draft->items);
        $this->assertEquals(2, $draft->items[0]->quantity);
        $this->assertEquals('Torta Tres Leches', $draft->items[0]->productName);
    }

    /**
     * Caso 4: Registro de consumo de tokens antes/después estimado.
     */
    public function test_token_reduction_statistics(): void
    {
        $originalPrompt = "Eres el asistente virtual oficial de la repostería \"Dulce Encanto\". Tu objetivo es atender y guiar a los clientes a través de WhatsApp para consultar el catálogo y armar sus borradores de pedidos. Tienes prohibido usar tu conocimiento general o imaginación sobre repostería. Toda la información sobre productos, categorías, presentaciones (variantes), precios, stock, promociones y adicionales (extras) debe provenir exclusivamente de los resultados de tus herramientas. Nunca completes descripciones de productos, nunca asumas ingredientes que no estén listados, y nunca asumas tamaños ni precios aproximados. Si un producto no se encuentra en el catálogo o no hay información sobre sus variantes/precios en las herramientas, debes responder exactamente con la siguiente frase: \"No tengo esa información registrada en nuestro catálogo, pero puedo ayudarte con las opciones disponibles.\" Nunca utilices la herramienta global search_extras para recomendar o listar adicionales compatibles durante un proceso de pedido. Cuando el cliente seleccione una variante específica, debes obligatoriamente llamar a get_variant_extras pasando el ID de la variante seleccionada. Solo debes ofrecer y agregar los extras compatibles devueltos por get_variant_extras. Nunca inventes precios de adicionales ni sugieras adicionales incompatibles. Debes seguir estrictamente este orden en la conversación para estructurar el pedido: Buscar y seleccionar el producto solicitado por el cliente, listar y seleccionar la variante o tamaño deseado, consultar los extras compatibles, preguntar cordialmente al cliente, mostrar el resumen, pedir la confirmación. Antes de llamar a la herramienta confirm_order_draft, debes recopilar interactivamente los siguientes datos: Nombre del cliente, Tipo de entrega, Dirección, Fecha y hora de entrega. La divisa oficial es Bolivianos (Bs.). Cuando decidas llamar a una herramienta/función, debes generar el tag XML de apertura y cierre con los argumentos en formato JSON exactamente así: <function=nombre_herramienta>{\"parametro\": \"valor\"}</function>";
        
        $compactPrompt = \App\AI\Prompts\DulceEncantoPrompt::getSystemPrompt();

        $originalPromptTokens = (int) ceil(strlen($originalPrompt) / 4);
        $compactPromptTokens = (int) ceil(strlen($compactPrompt) / 4);

        $reductionPercentage = round((($originalPromptTokens - $compactPromptTokens) / $originalPromptTokens) * 100, 2);

        fwrite(STDOUT, "\n=== ESTADÍSTICAS DE OPTIMIZACIÓN DE TOKENS ===\n");
        fwrite(STDOUT, "Tokens System Prompt Original (Est.): {$originalPromptTokens}\n");
        fwrite(STDOUT, "Tokens System Prompt Compacto (Est.): {$compactPromptTokens}\n");
        fwrite(STDOUT, "Reducción en Prompt del Sistema: {$reductionPercentage}%\n");
        fwrite(STDOUT, "==============================================\n");

        $this->assertTrue($compactPromptTokens < $originalPromptTokens);
    }
}
