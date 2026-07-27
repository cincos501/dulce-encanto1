<?php

declare(strict_types=1);

namespace App\AI\Registry;

use App\AI\Contracts\ToolInterface;

class ToolRegistry
{
    /**
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];

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
     * Get registered tools in the OpenAI/Groq tool schema format with dynamic context filtering.
     */
    public function getToolsSchema(?string $lastMessage = null, bool $hasActiveDraft = false): array
    {
        $schema = [];
        
        $includeOrderTools = $hasActiveDraft;
        
        if (!$includeOrderTools && $lastMessage !== null) {
            $lastMessage = strtolower($lastMessage);
            $keywords = ['torta', 'comprar', 'precio', 'agregar', 'pedido', 'adicional', 'extra', 'confirmar', 'tamaño', 'quiero', 'porciones', 'mediana', 'grande', 'chica', 'cupcake', 'postre', 'presentacion', 'variante', 'sku', 'carrito', 'borrador'];
            foreach ($keywords as $kw) {
                if (str_contains($lastMessage, $kw)) {
                    $includeOrderTools = true;
                    break;
                }
            }
        }

        foreach ($this->tools as $tool) {
            $name = $tool->getName();
            
            // Skip order modification tools if they are not needed in current context to save tokens
            if (!$includeOrderTools) {
                $orderTools = [
                    'add_to_order_draft',
                    'remove_from_order_draft',
                    'update_order_item_quantity',
                    'get_order_draft_summary',
                    'clear_order_draft',
                    'add_extra_to_order_item',
                    'remove_extra_from_order_item',
                    'confirm_order_draft',
                    'get_variant_extras',
                    'search_variants',
                    'search_extras'
                ];
                if (in_array($name, $orderTools, true)) {
                    continue;
                }
            }

            $parameters = $tool->getParameters();
            if (isset($parameters['properties']) && is_array($parameters['properties']) && empty($parameters['properties'])) {
                $parameters['properties'] = (object) [];
            }
            $schema[] = [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => $tool->getDescription(),
                    'parameters' => $parameters,
                ],
            ];
        }
        return $schema;
    }
}
