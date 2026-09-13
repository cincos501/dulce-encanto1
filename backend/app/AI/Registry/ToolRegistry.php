<?php

declare(strict_types=1);

namespace App\AI\Registry;

use App\AI\Contracts\ToolInterface;

class ToolRegistry
{
    protected string $lastIntent = 'catalogo';

    protected array $lastToolNames = [];

    public function __construct(array $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    /**
     * Register a tool in the registry.
     */
    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * Retrieve a tool by name.
     */
    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get the last detected intent.
     */
    public function getLastIntent(): string
    {
        return $this->lastIntent;
    }

    /**
     * Get the last sent tool names list.
     */
    public function getLastToolNames(): array
    {
        return $this->lastToolNames;
    }

    /**
     * Get registered tools in the OpenAI/Groq tool schema format with dynamic intent classification.
     */
    public function getToolsSchema(array|string|null $historyOrLastMessage = null, bool $hasActiveDraft = false): array
    {
        $lastMessage = null;
        $history = [];
        if (is_string($historyOrLastMessage)) {
            $lastMessage = $historyOrLastMessage;
        } elseif (is_array($historyOrLastMessage)) {
            $history = $historyOrLastMessage;
            for ($i = count($history) - 1; $i >= 0; $i--) {
                if (($history[$i]['role'] ?? '') === 'user') {
                    $lastMessage = $history[$i]['content'] ?? null;
                    break;
                }
            }
        }

        $intent = $this->detectIntent($lastMessage, $hasActiveDraft);
        $this->lastIntent = $intent;

        $allowedTools = [];
        if ($intent === 'confirmacion') {
            $allowedTools = ['confirm_order_draft', 'get_order_draft_summary', 'get_order_history_link', 'get_bakery_location'];
        } elseif ($intent === 'pedido') {
            $allowedTools = [
                'add_to_order_draft',
                'update_order_item_quantity',
                'remove_from_order_draft',
                'add_extra_to_order_item',
                'remove_extra_from_order_item',
                'get_order_draft_summary',
                'get_variant_extras',
                'search_products',
                'search_variants',
                'get_order_history_link',
            ];
            if ($hasActiveDraft) {
                $allowedTools[] = 'confirm_order_draft';
            }
        } else { // catalogo
            $allowedTools = [
                'search_products',
                'search_categories',
                'search_variants',
                'get_variant_extras',
                'search_promotions',
                'get_business_info',
                'get_opening_hours',
                'get_bakery_location',
                'get_order_history_link',
            ];
        }

        // Check if a variant was selected
        $variantSelected = self::isVariantSelected($history, $lastMessage);
        // If no variant is selected, remove get_variant_extras from allowedTools
        // EXCEPT if the user is explicitly asking about extras/adicionales (so that Groq doesn't crash on tool call generation).
        if (! $variantSelected) {
            $isAskingForExtras = false;
            if ($lastMessage !== null) {
                $text = strtolower($lastMessage);
                if (str_contains($text, 'adicional') || str_contains($text, 'extra') || str_contains($text, 'topping')) {
                    $isAskingForExtras = true;
                }
            }
            if (! $isAskingForExtras) {
                $allowedTools = array_values(array_filter($allowedTools, fn ($t) => $t !== 'get_variant_extras'));
            }
        }

        $schema = [];
        $sentToolNames = [];

        foreach ($this->tools as $tool) {
            $name = $tool->getName();

            // Allow all tools when lastMessage is null to maintain compatibility with test suites
            if ($lastMessage !== null && ! in_array($name, $allowedTools, true)) {
                continue;
            }

            $parameters = $tool->getParameters();
            if (isset($parameters['properties']) && is_array($parameters['properties'])) {
                if (empty($parameters['properties'])) {
                    $parameters['properties'] = (object) [];
                } else {
                    $required = $parameters['required'] ?? [];
                    foreach ($parameters['properties'] as $propName => $propDetails) {
                        if (! in_array($propName, $required, true)) {
                            if (isset($propDetails['type'])) {
                                if (is_string($propDetails['type'])) {
                                    $propDetails['type'] = [$propDetails['type'], 'null'];
                                } elseif (is_array($propDetails['type'])) {
                                    if (! in_array('null', $propDetails['type'], true)) {
                                        $propDetails['type'][] = 'null';
                                    }
                                }
                            }
                        }
                        $parameters['properties'][$propName] = $propDetails;
                    }
                }
            }

            $schema[] = [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => $tool->getDescription(),
                    'parameters' => $parameters,
                ],
            ];
            $sentToolNames[] = $name;
        }

        $this->lastToolNames = $sentToolNames;

        return $schema;
    }

    /**
     * Helper to classify user intent.
     */
    public function detectIntent(?string $lastMessage, bool $hasActiveDraft): string
    {
        if ($lastMessage === null) {
            return 'catalogo';
        }

        $text = strtolower($lastMessage);

        // Caso ubicación de la pastelería: se mantiene en 'catalogo' (donde vive get_bakery_location)
        // aunque el mensaje contenga palabras como "dirección" que normalmente irían a confirmación.
        $locationKeywords = ['donde estan', 'dónde están', 'donde queda', 'dónde queda', 'donde se ubican', 'como llego', 'cómo llego', 'como llegar', 'cómo llegar', 'su ubicacion', 'su ubicación', 'la ubicacion', 'la ubicación', 'pasame la ubicacion', 'pásame la ubicación', 'ubicados', 'el mapa', 'google maps'];
        foreach ($locationKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return 'catalogo';
            }
        }

        // Caso confirmación explícita o datos de entrega
        $confirmationKeywords = [
            'confirmar', 'confirma', 'finalizar', 'pagar', 'pago', 'direccion', 'dirección', 'entrega', 'retiro',
            'nombre', 'fecha', 'hora', 'tienda', 'delivery', 'mañana', 'manana', 'hoy', 'mediodia', 'mediodía',
        ];
        foreach ($confirmationKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return 'confirmacion';
            }
        }

        // Caso respuestas afirmativas y confirmación con borrador activo
        if ($hasActiveDraft) {
            $affirmationKeywords = [
                'si', 'sí', 'sii', 'siii', 'claro', 'dale', 'de acuerdo', 'ok', 'listo', 'proceder',
                'generar', 'correcto', 'exacto', 'por favor', 'hazlo', 'hacer el pedido', 'registralo',
                'regístralo', 'confirmo', 'ya', 'adelante',
            ];
            $words = preg_split('/[\s,\.\?!;:]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($words as $w) {
                if (in_array($w, $affirmationKeywords, true)) {
                    return 'confirmacion';
                }
            }
            foreach ($affirmationKeywords as $kw) {
                if (str_contains($text, $kw)) {
                    return 'confirmacion';
                }
            }
        }

        // Caso pedido
        $orderKeywords = ['agregar', 'adiciona', 'adicional', 'extra', 'topping', 'quitar', 'remover', 'eliminar', 'cantidad', 'carrito', 'pedido', 'resumen', 'dame', 'quiero una', 'quiero un', 'ponle', 'variante', 'porcion', 'porción', 'completa', 'mediana', 'grande', 'chica', 'sku', 'id'];
        foreach ($orderKeywords as $kw) {
            if (str_contains($text, $kw)) {
                return 'pedido';
            }
        }

        // If they have an active draft, default to pedido, otherwise catálogo
        return $hasActiveDraft ? 'pedido' : 'catalogo';
    }

    /**
     * Check if a variant has been selected from the catalog search_variants response.
     */
    public static function isVariantSelected(array $history, ?string $lastMessage = null): bool
    {
        $lastSearchVariantsContent = null;
        $searchVariantsIndex = -1;

        foreach ($history as $idx => $msg) {
            if (($msg['role'] ?? '') === 'tool' && ($msg['name'] ?? '') === 'search_variants') {
                $lastSearchVariantsContent = $msg['content'] ?? '';
                $searchVariantsIndex = $idx;
            }
        }

        if ($searchVariantsIndex === -1 || empty($lastSearchVariantsContent)) {
            return false;
        }

        $variantIdentifiers = [];

        // Extract IDs using regex [ID Variante: \d+]
        if (preg_match_all('/\[ID Variante:\s*(\d+)\]/i', $lastSearchVariantsContent, $matchesId)) {
            foreach ($matchesId[1] as $id) {
                $variantIdentifiers[] = trim($id);
            }
        }

        // Extract presentation names using Presentación: ...
        if (preg_match_all('/Presentación:\s*([^| \n\r\t\-]+)/i', $lastSearchVariantsContent, $matchesName)) {
            foreach ($matchesName[1] as $name) {
                $variantIdentifiers[] = strtolower(trim($name));
            }
        }

        // Accent removal/cleaning function
        $cleanString = function (string $text) {
            $utf8 = [
                '/[áàâä]/u' => 'a',
                '/[éèêë]/u' => 'e',
                '/[íìîï]/u' => 'i',
                '/[óòôö]/u' => 'o',
                '/[úùûü]/u' => 'u',
                '/[ñ]/u' => 'n',
            ];

            return preg_replace(array_keys($utf8), array_values($utf8), $text);
        };

        $normalizedIdentifiers = [];
        foreach ($variantIdentifiers as $id) {
            $norm = strtolower($cleanString($id));
            $normalizedIdentifiers[] = $norm;
            $parts = preg_split('/[\s\-]+/', $norm);
            foreach ($parts as $part) {
                if (strlen($part) > 2) {
                    $normalizedIdentifiers[] = $part;
                }
            }
        }
        $normalizedIdentifiers = array_unique(array_filter($normalizedIdentifiers));

        if (empty($normalizedIdentifiers)) {
            $normalizedIdentifiers = ['porcion', 'completa', 'mediana', 'grande', 'chica', 'pequena', 'caja', 'unidad'];
        }

        for ($i = 0; $i < count($history); $i++) {
            if (($history[$i]['role'] ?? '') === 'user') {
                $userMsg = strtolower($cleanString($history[$i]['content'] ?? ''));
                if (str_contains($userMsg, 'adicional') || str_contains($userMsg, 'extra') || str_contains($userMsg, 'topping')) {
                    continue;
                }
                foreach ($normalizedIdentifiers as $identifier) {
                    if (str_contains($userMsg, $identifier)) {
                        return true;
                    }
                }
            }
        }

        if ($lastMessage !== null) {
            $userMsg = strtolower($cleanString($lastMessage));
            if (! str_contains($userMsg, 'adicional') && ! str_contains($userMsg, 'extra') && ! str_contains($userMsg, 'topping')) {
                foreach ($normalizedIdentifiers as $identifier) {
                    if (str_contains($userMsg, $identifier)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
