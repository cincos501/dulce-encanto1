<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Tools\Catalog\SearchProductsTool;
use App\AI\Tools\Catalog\SearchCategoriesTool;
use App\AI\Tools\Catalog\SearchVariantsTool;
use App\AI\Tools\Catalog\SearchExtrasTool;
use App\AI\Tools\Promotions\SearchPromotionsTool;
use App\AI\Tools\Business\GetBusinessInfoTool;
use App\AI\Tools\Business\GetOpeningHoursTool;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\ProductVariantRepositoryInterface;
use App\Repositories\ExtraRepositoryInterface;
use App\Repositories\PromotionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class ToolsTest extends TestCase
{
    public function test_get_business_info_tool(): void
    {
        $tool = new GetBusinessInfoTool();
        $this->assertEquals('get_business_info', $tool->getName());
        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('Los Claveles', $tool->execute([]));
    }

    public function test_get_opening_hours_tool(): void
    {
        $tool = new GetOpeningHoursTool();
        $this->assertEquals('get_opening_hours', $tool->getName());
        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('Lunes a Viernes', $tool->execute([]));
    }

    public function test_search_products_tool_returns_results(): void
    {
        $mockPaginator = $this->mock(LengthAwarePaginator::class, function (MockInterface $mock) {
            $mock->shouldReceive('isEmpty')->once()->andReturn(false);
            
            $fakeProduct = (object)[
                'id' => 1,
                'name' => 'Torta de Selva Negra',
                'description' => 'Deliciosa torta con cerezas',
                'category' => (object)['name' => 'Pasteles']
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
            $fakeCategory = (object)[
                'id' => 1,
                'name' => 'Galletas',
                'description' => 'Galletas artesanales crujientes'
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
