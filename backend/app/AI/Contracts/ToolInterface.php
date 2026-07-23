<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface ToolInterface
{
    /**
     * Get the tool name (e.g. search_products).
     */
    public function getName(): string;

    /**
     * Get the tool description for the LLM.
     */
    public function getDescription(): string;

    /**
     * Get the parameters schema (JSON Schema object format) for the tool.
     */
    public function getParameters(): array;

    /**
     * Execute the tool with the provided arguments and return a string result.
     *
     * @param array $arguments
     * @param array $context
     * @return string
     */
    public function execute(array $arguments, array $context = []): string;
}
