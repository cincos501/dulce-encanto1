<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Contracts\ToolInterface;
use App\AI\Registry\ToolRegistry;
use Tests\TestCase;

class ToolRegistryTest extends TestCase
{
    public function test_can_register_and_retrieve_tool(): void
    {
        $fakeTool = new class implements ToolInterface {
            public function getName(): string { return 'fake_tool'; }
            public function getDescription(): string { return 'A fake tool for testing'; }
            public function getParameters(): array {
                return [
                    'type' => 'object',
                    'properties' => [
                        'param1' => ['type' => 'string']
                    ],
                    'required' => ['param1']
                ];
            }
            public function execute(array $arguments, array $context = []): string {
                return "Executed with " . ($arguments['param1'] ?? '');
            }
        };

        $registry = new ToolRegistry([$fakeTool]);

        $this->assertSame($fakeTool, $registry->get('fake_tool'));
        $this->assertNull($registry->get('non_existent'));

        $schema = $registry->getToolsSchema();
        $this->assertCount(1, $schema);
        $this->assertEquals('fake_tool', $schema[0]['function']['name']);
        $this->assertEquals('A fake tool for testing', $schema[0]['function']['description']);
        $this->assertEquals('Executed with val1', $fakeTool->execute(['param1' => 'val1']));
    }
}
