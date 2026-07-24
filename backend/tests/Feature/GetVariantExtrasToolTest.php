<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Tools\Catalog\GetVariantExtrasTool;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Extra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetVariantExtrasToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_variant_extras_tool_returns_associated_extras_with_pivot_prices(): void
    {
        // 1. Seed database with test catalog
        $category = Category::create([
            'name' => 'Pasteles',
            'description' => 'Tortas y postres',
            'is_active' => true
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Torta Selva Negra',
            'description' => 'Bizcocho de chocolate',
            'is_active' => true
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Porción',
            'price' => 25.00,
            'sku' => 'SN-PORC',
            'serves_people' => 1,
            'is_active' => true
        ]);

        $extra1 = Extra::create([
            'name' => 'Cerezas Extra',
            'is_active' => true
        ]);

        $extra2 = Extra::create([
            'name' => 'Crema Chantilly',
            'is_active' => true
        ]);

        // Associate extra1 to our variant with a specific pivot price
        $variant->extras()->attach($extra1->id, ['price' => 5.50]);
        // Associate extra2 to our variant with another pivot price
        $variant->extras()->attach($extra2->id, ['price' => 3.00]);

        // Create an unrelated extra to verify it doesn't get returned
        $unrelatedExtra = Extra::create([
            'name' => 'Velas',
            'is_active' => true
        ]);

        // 2. Instantiate tool and execute
        $tool = $this->app->make(GetVariantExtrasTool::class);
        $response = $tool->execute(['variant_id' => $variant->id]);

        // 3. Assert response structure and contents
        $data = json_decode($response, true);
        $this->assertNotNull($data);
        $this->assertEquals($variant->id, $data['variant_id']);
        $this->assertEquals('Porción', $data['variant_name']);
        
        $this->assertCount(2, $data['extras']);
        
        $extrasMapped = collect($data['extras'])->keyBy('id');
        
        $this->assertTrue($extrasMapped->has($extra1->id));
        $this->assertEquals('Cerezas Extra', $extrasMapped->get($extra1->id)['name']);
        $this->assertEquals(5.50, $extrasMapped->get($extra1->id)['price']);
        
        $this->assertTrue($extrasMapped->has($extra2->id));
        $this->assertEquals('Crema Chantilly', $extrasMapped->get($extra2->id)['name']);
        $this->assertEquals(3.00, $extrasMapped->get($extra2->id)['price']);

        // Assert that the unrelated extra is not in the response
        $this->assertFalse($extrasMapped->has($unrelatedExtra->id));
    }

    public function test_get_variant_extras_tool_returns_error_for_non_existent_variant(): void
    {
        $tool = $this->app->make(GetVariantExtrasTool::class);
        $response = $tool->execute(['variant_id' => 99999]);

        $data = json_decode($response, true);
        $this->assertNotNull($data);
        $this->assertFalse($data['success'] ?? true);
        $this->assertStringContainsString('no fue encontrada en el catálogo', $data['error']);
    }
}
