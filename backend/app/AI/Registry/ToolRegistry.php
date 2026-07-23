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
     * Get all registered tools in the OpenAI/Groq tool schema format.
     */
    public function getToolsSchema(): array
    {
        $schema = [];
        foreach ($this->tools as $tool) {
            $parameters = $tool->getParameters();
            if (isset($parameters['properties']) && is_array($parameters['properties']) && empty($parameters['properties'])) {
                $parameters['properties'] = (object) [];
            }
            $schema[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $parameters,
                ],
            ];
        }
        return $schema;
    }
}
