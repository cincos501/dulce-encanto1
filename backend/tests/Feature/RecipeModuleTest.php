<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\ProductVariant;
use App\Models\Supply;
use App\Models\Recipe;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecipeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected ProductVariant $variant;
    protected Supply $supply;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        // Create Admin User
        $this->adminUser = User::factory()->create([
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('Administrador');

        // Create dummy product & variant
        $product = \App\Models\Product::create([
            'name' => 'Torta de Receta',
            'description' => 'Descripción',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Porción',
            'sku' => 'TEST-SKU-1',
            'price' => 3.50,
            'is_active' => true,
        ]);

        $this->supply = Supply::create([
            'name' => 'Harina Integral',
            'unit' => 'kg',
            'stock' => 50.00,
            'minimum_stock' => 10.00,
            'average_cost' => 1.50,
            'is_active' => true,
        ]);
    }

    /**
     * Test retrieving all recipes.
     */
    public function test_can_retrieve_recipes_list(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'success',
                'message'
            ]);
    }

    /**
     * Test retrieving a specific recipe details.
     */
    public function test_can_retrieve_single_recipe(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Create a recipe record
        Recipe::create([
            'product_variant_id' => $this->variant->id,
            'supply_id' => $this->supply->id,
            'quantity' => 0.50,
            'unit' => 'kg',
        ]);

        $response = $this->getJson("/api/v1/recipes/{$this->variant->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->variant->id
            ]);
    }

    /**
     * Test updating a recipe where the product_variant_id is omitted from the request body.
     */
    public function test_can_update_recipe_without_product_variant_id_in_payload(): void
    {
        Sanctum::actingAs($this->adminUser);

        $payload = [
            'items' => [
                [
                    'supply_id' => $this->supply->id,
                    'quantity' => 0.75,
                    'unit' => 'kg',
                    'observation' => 'Harina especial'
                ]
            ]
        ];

        $response = $this->putJson("/api/v1/recipes/{$this->variant->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify changes are in database
        $this->assertDatabaseHas('recipes', [
            'product_variant_id' => $this->variant->id,
            'supply_id' => $this->supply->id,
            'quantity' => 0.75,
            'unit' => 'kg',
            'observation' => 'Harina especial'
        ]);
    }

    /**
     * Test validation fails when empty items are sent.
     */
    public function test_update_recipe_validation_fails_for_empty_items(): void
    {
        Sanctum::actingAs($this->adminUser);

        $payload = [
            'items' => []
        ];

        $response = $this->putJson("/api/v1/recipes/{$this->variant->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }
}
