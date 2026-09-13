<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Tools\Business\GetBusinessInfoTool;
use App\AI\Tools\Business\GetOpeningHoursTool;
use App\AI\Tools\Catalog\SearchCategoriesTool;
use App\AI\Tools\Catalog\SearchProductsTool;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class ToolsTest extends TestCase
{
    public function test_get_business_info_tool(): void
    {
        config()->set('business.name', 'Dulce Encanto');
        config()->set('business.description', 'Repostería de Tarija.');
        config()->set('business.location.address', 'Av. Circunvalación, Barrio Carlos Wagner, Tarija, Bolivia');
        config()->set('business.location.maps_url', 'https://maps.google.com/?q=-21.5,-64.75');
        config()->set('business.phone', '+591 70012345');

        $tool = new GetBusinessInfoTool;
        $this->assertEquals('get_business_info', $tool->getName());
        $this->assertNotEmpty($tool->getDescription());

        $output = $tool->execute([]);
        $this->assertStringContainsString('Av. Circunvalación, Barrio Carlos Wagner, Tarija, Bolivia', $output);
        $this->assertStringContainsString('https://maps.google.com/?q=-21.5,-64.75', $output);
        $this->assertStringContainsString('Bolivianos (Bs.)', $output);
    }

    public function test_get_opening_hours_tool(): void
    {
        $tool = new GetOpeningHoursTool;
        $this->assertEquals('get_opening_hours', $tool->getName());
        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('Lunes a Viernes', $tool->execute([]));
    }

    public function test_search_products_tool_returns_results(): void
    {
        $mockPaginator = $this->mock(LengthAwarePaginator::class, function (MockInterface $mock) {
            $mock->shouldReceive('isEmpty')->once()->andReturn(false);

            $fakeProduct = (object) [
                'id' => 1,
                'name' => 'Torta de Selva Negra',
                'description' => 'Deliciosa torta con cerezas',
                'category' => (object) ['name' => 'Pasteles'],
            ];
            $mock->shouldReceive('items')->once()->andReturn([$fakeProduct]);
        });

        $mockRepo = $this->mock(ProductRepositoryInterface::class, function (MockInterface $mock) use ($mockPaginator) {
            $mock->shouldReceive('paginate')
                ->once()
                ->with(15, 'selva', true)
                ->andReturn($mockPaginator);
        });

        $tool = new SearchProductsTool($mockRepo);
        $result = $tool->execute(['query' => 'selva']);
        $this->assertStringContainsString('Selva Negra', $result);
    }

    public function test_search_categories_tool_returns_results(): void
    {
        $mockRepo = $this->mock(CategoryRepositoryInterface::class, function (MockInterface $mock) {
            $fakeCategory = (object) [
                'id' => 1,
                'name' => 'Galletas',
                'description' => 'Galletas artesanales crujientes',
            ];
            $mock->shouldReceive('all')
                ->once()
                ->with(true)
                ->andReturn(collect([$fakeCategory]));
        });

        $tool = new SearchCategoriesTool($mockRepo);
        $result = $tool->execute([]);
        $this->assertStringContainsString('Galletas', $result);
    }
}
