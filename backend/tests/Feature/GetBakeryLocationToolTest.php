<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Registry\ToolRegistry;
use App\AI\Tools\Business\GetBakeryLocationTool;
use Tests\TestCase;

class GetBakeryLocationToolTest extends TestCase
{
    public function test_returns_address_and_google_maps_link(): void
    {
        config()->set('business.location', [
            'address' => 'Av. Circunvalación, Barrio Carlos Wagner, Tarija, Bolivia',
            'maps_url' => 'https://maps.google.com/?q=-21.50631914541871,-64.75171651298785',
            'reference' => 'A pocos metros de la Av. Los Crespones.',
        ]);

        $result = (new GetBakeryLocationTool)->execute([]);

        $this->assertStringContainsString('Av. Circunvalación, Barrio Carlos Wagner, Tarija, Bolivia', $result);
        $this->assertStringContainsString('https://maps.google.com/?q=-21.50631914541871,-64.75171651298785', $result);
        $this->assertStringContainsString('A pocos metros de la Av. Los Crespones.', $result);
    }

    public function test_tool_is_exposed_in_catalog_intent_schema(): void
    {
        $registry = app(ToolRegistry::class);

        $schema = $registry->getToolsSchema('¿dónde están ubicados?');
        $names = array_map(fn ($t) => $t['function']['name'], $schema);

        $this->assertContains('get_bakery_location', $names);
    }

    public function test_location_query_with_the_word_direccion_stays_in_catalog_intent(): void
    {
        $registry = app(ToolRegistry::class);

        $this->assertSame('catalogo', $registry->detectIntent('¿me pasas su ubicación y dirección?', false));
    }
}
