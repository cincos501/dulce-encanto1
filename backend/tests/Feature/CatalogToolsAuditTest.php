<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Extra;
use App\Models\Promotion;
use App\Repositories\ProductVariantRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ExtraRepository;
use App\Repositories\PromotionRepository;
use App\Repositories\ProductRepository;
use App\AI\Tools\Catalog\SearchVariantsTool;
use App\AI\Tools\Catalog\SearchCategoriesTool;
use App\AI\Tools\Catalog\SearchExtrasTool;
use App\AI\Tools\Promotions\SearchPromotionsTool;
use App\AI\Tools\Catalog\SearchProductsTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogToolsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Product $product;
    protected ProductVariant $variantMediana;
    protected ProductVariant $variantGrande;
    protected Extra $extraNutella;
    protected Promotion $promoWinter;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a Category
        $this->category = Category::create([
            'name' => 'Tortas Especiales',
            'description' => 'Tortas para ocasiones especiales',
            'is_active' => true,
        ]);

        // 2. Create a Product
        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Torta Selva Negra',
            'description' => 'Torta de chocolate con cerezas y crema',
            'is_active' => true,
        ]);

        // 3. Create Product Variants
        $this->variantMediana = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Mediana',
            'sku' => 'VAR-SELVA-MED',
            'price' => 120.00,
            'serves_people' => 10,
            'is_active' => true,
        ]);

        $this->variantGrande = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Grande',
            'sku' => 'VAR-SELVA-GRA',
            'price' => 180.00,
            'serves_people' => 20,
            'is_active' => true,
        ]);

        // 4. Create an Extra
        $this->extraNutella = Extra::create([
            'name' => 'Topping Nutella',
            'description' => 'Nutella cremosa extra',
            'is_active' => true,
        ]);

        // Associate Extra with Variant Mediana via pivot
        $this->variantMediana->extras()->attach($this->extraNutella->id, ['price' => 15.00]);

        // 5. Create a Promotion
        $this->promoWinter = Promotion::create([
            'name' => 'Descuento Invernal',
            'description' => 'Gran oferta de invierno',
            'discount_type' => 'percentage',
            'discount' => 15.00,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(5),
            'is_active' => true,
        ]);

        // Associate Promotion with Variant Mediana
        $this->variantMediana->promotions()->attach($this->promoWinter->id);
    }

    /**
     * Caso 1: product_id + query incompatible en SearchVariantsTool.
     * Debería ignorar la query incompatible y retornar las variantes del producto.
     */
    public function test_search_variants_ignores_query_when_product_id_is_provided(): void
    {
        $repo = new ProductVariantRepository();
        $tool = new SearchVariantsTool($repo);

        $result = $tool->execute([
            'product_id' => $this->product->id,
            'query' => 'incompatible_text_that_would_yield_zero_results'
        ]);

        $this->assertStringContainsString('Torta Selva Negra', $result);
        $this->assertStringContainsString('Mediana', $result);
        $this->assertStringContainsString('Grande', $result);
    }

    /**
     * Caso 2: Usuario busca con query genérico en SearchCategoriesTool (ej. "muéstrame categorías").
     * Debería retornar todas las categorías en lugar de filtrar literalmente.
     */
    public function test_search_categories_returns_all_on_generic_query(): void
    {
        $repo = new CategoryRepository();
        $tool = new SearchCategoriesTool($repo);

        $result = $tool->execute([
            'query' => 'muéstrame categorías'
        ]);

        $this->assertStringContainsString('Tortas Especiales', $result);
    }

    /**
     * Caso 3: Usuario busca con query genérico en SearchPromotionsTool (ej. "qué promociones tienen").
     * Debería retornar todas las promociones con su porcentaje/precio de descuento correcto.
     */
    public function test_search_promotions_returns_all_and_formats_discount_correctly(): void
    {
        $repo = new PromotionRepository();
        $tool = new SearchPromotionsTool($repo);

        $result = $tool->execute([
            'query' => 'qué promociones tienen'
        ]);

        $this->assertStringContainsString('Descuento Invernal', $result);
        $this->assertStringContainsString('Descuento: 15%', $result); // Correct formatting from new discount properties
    }

    /**
     * Caso 4: Usuario busca con query genérico en SearchExtrasTool (ej. "qué extras tienen").
     * Debería retornar todos los adicionales omitiendo precio global ficticio.
     */
    public function test_search_extras_returns_all_and_omits_global_price(): void
    {
        $repo = new ExtraRepository();
        $tool = new SearchExtrasTool($repo);

        $result = $tool->execute([
            'query' => 'adicionales'
        ]);

        $this->assertStringContainsString('Topping Nutella', $result);
        $this->assertStringNotContainsString('Precio: Bs.', $result); // No direct global prices
        $this->assertStringContainsString('get_variant_extras', $result); // References instructions tool
    }

    /**
     * Caso 5: Búsqueda textual específica.
     * Debería funcionar correctamente y encontrar coincidencias textuales reales.
     */
    public function test_search_products_textual_search_works_correctly(): void
    {
        $repo = new ProductRepository();
        $tool = new SearchProductsTool($repo);

        $result = $tool->execute([
            'query' => 'Selva Negra'
        ]);

        $this->assertStringContainsString('Torta Selva Negra', $result);
        $this->assertStringContainsString('Tortas Especiales', $result);
    }
}
