<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Tools\Orders\AddToOrderDraftTool;
use App\AI\Tools\Orders\RemoveFromOrderDraftTool;
use App\AI\Tools\Orders\UpdateOrderItemQuantityTool;
use App\AI\Tools\Orders\AddExtraToOrderItemTool;
use App\AI\Tools\Orders\RemoveExtraFromOrderItemTool;
use App\AI\Tools\Orders\GetOrderDraftSummaryTool;
use App\AI\Tools\Orders\ClearOrderDraftTool;
use App\AI\Orders\OrderDraftManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Extra;
use App\Repositories\WhatsAppSessionRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderToolsTest extends TestCase
{
    use RefreshDatabase;

    protected OrderDraftManager $draftManager;
    protected string $phone = '59170012345';

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

        $this->draftManager = $this->app->make(OrderDraftManager::class);
        $this->draftManager->clearDraft($this->phone);
    }

    public function test_order_draft_tools_integration_flow(): void
    {
        // 1. Seed catalog data
        $category = Category::create([
            'name' => 'Pasteles',
            'description' => 'Pasteles deliciosos',
            'status' => 'active'
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Torta Tres Leches',
            'description' => 'Torta húmeda tradicional',
            'price' => 120.0,
            'status' => 'active'
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Mediana',
            'price' => 120.0,
            'sku' => 'PAST-TL-MED',
            'serves_people' => 10,
            'status' => 'active'
        ]);

        $extra = Extra::create([
            'name' => 'Chispas de Chocolate',
            'status' => 'active'
        ]);

        // Associate the extra to the variant on the pivot table
        $variant->extras()->attach($extra->id, ['price' => 10.0]);

        $context = ['phone' => $this->phone];

        // 2. Test AddToOrderDraftTool
        $addTool = $this->app->make(AddToOrderDraftTool::class);
        $response = $addTool->execute(['variant_id' => $variant->id, 'quantity' => 2], $context);
        $this->assertStringContainsString('Se agregaron 2 unidad(es)', $response);
        $this->assertStringContainsString('Bs. 240', $response);

        // 3. Test AddExtraToOrderItemTool
        $addExtraTool = $this->app->make(AddExtraToOrderItemTool::class);
        $response = $addExtraTool->execute(['variant_id' => $variant->id, 'extra_id' => $extra->id], $context);
        $this->assertStringContainsString('Chispas de Chocolate', $response);
        // unit price (120) + extra price (10) = 130 * 2 = 260
        $this->assertStringContainsString('Bs. 260', $response);

        // 4. Test GetOrderDraftSummaryTool
        $summaryTool = $this->app->make(GetOrderDraftSummaryTool::class);
        $response = $summaryTool->execute([], $context);
        $this->assertStringContainsString('Torta Tres Leches', $response);
        $this->assertStringContainsString('Chispas de Chocolate', $response);
        $this->assertStringContainsString('Bs. 260', $response);

        // 5. Test UpdateOrderItemQuantityTool
        $updateTool = $this->app->make(UpdateOrderItemQuantityTool::class);
        $response = $updateTool->execute(['variant_id' => $variant->id, 'quantity' => 3], $context);
        $this->assertStringContainsString('Cantidad actualizada a 3', $response);
        // 130 * 3 = 390
        $this->assertStringContainsString('Bs. 390', $response);

        // 6. Test RemoveExtraFromOrderItemTool
        $removeExtraTool = $this->app->make(RemoveExtraFromOrderItemTool::class);
        $response = $removeExtraTool->execute(['variant_id' => $variant->id, 'extra_id' => $extra->id], $context);
        $this->assertStringContainsString('Adicional eliminado', $response);
        // unit price (120) * 3 = 360
        $this->assertStringContainsString('Bs. 360', $response);

        // 7. Test RemoveFromOrderDraftTool
        $removeTool = $this->app->make(RemoveFromOrderDraftTool::class);
        $response = $removeTool->execute(['variant_id' => $variant->id], $context);
        $this->assertStringContainsString('Producto eliminado', $response);
        $this->assertStringContainsString('Bs. 0', $response);

        // 8. Test ClearOrderDraftTool
        $clearTool = $this->app->make(ClearOrderDraftTool::class);
        $response = $clearTool->execute([], $context);
        $this->assertStringContainsString('vaciado completamente', $response);

        $this->draftManager->clearDraft($this->phone);
    }
}
