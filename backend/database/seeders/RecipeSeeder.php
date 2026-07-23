<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use App\Models\Supply;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recipesData = [
            // 1. Torta Selva Negra - Torta completa
            [
                'product' => 'Torta Selva Negra',
                'variant' => 'Torta completa',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.5000, 'unit' => 'kg', 'observation' => 'Harina cernida'],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.4000, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Mantequilla sin Sal', 'quantity' => 0.2500, 'unit' => 'kg', 'observation' => 'A temperatura ambiente'],
                    ['name' => 'Crema de Leche', 'quantity' => 0.3000, 'unit' => 'lt', 'observation' => 'Para batir'],
                    ['name' => 'Chocolate Amargo 60%', 'quantity' => 0.2000, 'unit' => 'kg', 'observation' => 'Para cobertura'],
                    ['name' => 'Huevos de Gallina', 'quantity' => 6.0000, 'unit' => 'un', 'observation' => ''],
                    ['name' => 'Cacao en Polvo', 'quantity' => 0.1000, 'unit' => 'kg', 'observation' => 'Cacao amargo'],
                ]
            ],
            // 2. Torta Selva Negra - Porción
            [
                'product' => 'Torta Selva Negra',
                'variant' => 'Porción',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.0500, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.0400, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Mantequilla sin Sal', 'quantity' => 0.0250, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Crema de Leche', 'quantity' => 0.0300, 'unit' => 'lt', 'observation' => ''],
                    ['name' => 'Chocolate Amargo 60%', 'quantity' => 0.0200, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Huevos de Gallina', 'quantity' => 0.6000, 'unit' => 'un', 'observation' => ''],
                ]
            ],
            // 3. Torta Tres Leches - Torta completa
            [
                'product' => 'Torta Tres Leches',
                'variant' => 'Torta completa',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.4000, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.3000, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Leche Entera', 'quantity' => 0.5000, 'unit' => 'lt', 'observation' => 'Para remojo'],
                    ['name' => 'Leche Condensada', 'quantity' => 1.0000, 'unit' => 'un', 'observation' => 'Lata de remojo'],
                    ['name' => 'Crema de Leche', 'quantity' => 0.4000, 'unit' => 'lt', 'observation' => 'Remojo y merengue'],
                    ['name' => 'Huevos de Gallina', 'quantity' => 6.0000, 'unit' => 'un', 'observation' => 'Separa claras y yemas'],
                ]
            ],
            // 4. Torta Tres Leches - Porción
            [
                'product' => 'Torta Tres Leches',
                'variant' => 'Porción',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.0400, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.0300, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Leche Entera', 'quantity' => 0.0500, 'unit' => 'lt', 'observation' => ''],
                    ['name' => 'Leche Condensada', 'quantity' => 0.1000, 'unit' => 'un', 'observation' => ''],
                    ['name' => 'Crema de Leche', 'quantity' => 0.0400, 'unit' => 'lt', 'observation' => ''],
                ]
            ],
            // 5. Cupcake de Vainilla - Unidad
            [
                'product' => 'Cupcake de Vainilla',
                'variant' => 'Unidad',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.0300, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.0250, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Mantequilla sin Sal', 'quantity' => 0.0200, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Huevos de Gallina', 'quantity' => 0.3000, 'unit' => 'un', 'observation' => ''],
                    ['name' => 'Esencia de Vainilla', 'quantity' => 0.0050, 'unit' => 'lt', 'observation' => 'Extracto puro'],
                ]
            ],
            // 6. Cupcake de Red Velvet - Unidad
            [
                'product' => 'Cupcake de Red Velvet',
                'variant' => 'Unidad',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.0300, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.0250, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Mantequilla sin Sal', 'quantity' => 0.0200, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Queso Crema', 'quantity' => 0.0300, 'unit' => 'kg', 'observation' => 'Para el frosting'],
                    ['name' => 'Cacao en Polvo', 'quantity' => 0.0050, 'unit' => 'kg', 'observation' => ''],
                ]
            ],
            // 7. Galletas de Chispas - Unidad
            [
                'product' => 'Galletas de Chispas',
                'variant' => 'Unidad',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.0400, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.0300, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Mantequilla sin Sal', 'quantity' => 0.0250, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Chocolate de Leche', 'quantity' => 0.0200, 'unit' => 'kg', 'observation' => 'Chispas picadas'],
                ]
            ],
            // 8. Galletas de Avena y Miel - Unidad
            [
                'product' => 'Galletas de Avena y Miel',
                'variant' => 'Unidad',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.0300, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.0200, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Mantequilla sin Sal', 'quantity' => 0.0250, 'unit' => 'kg', 'observation' => ''],
                ]
            ],
            // 9. Cheesecake de Frutilla - Entero
            [
                'product' => 'Cheesecake de Frutilla',
                'variant' => 'Entero',
                'ingredients' => [
                    ['name' => 'Queso Crema', 'quantity' => 0.8000, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.2000, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Crema de Leche', 'quantity' => 0.2000, 'unit' => 'lt', 'observation' => ''],
                    ['name' => 'Gelatina sin Sabor', 'quantity' => 0.0150, 'unit' => 'kg', 'observation' => ''],
                    ['name' => 'Frutillas Frescas', 'quantity' => 0.3000, 'unit' => 'kg', 'observation' => 'Para cobertura'],
                ]
            ],
            // 10. Pie de Limón - Entero
            [
                'product' => 'Pie de Limón',
                'variant' => 'Entero',
                'ingredients' => [
                    ['name' => 'Harina de Trigo', 'quantity' => 0.2500, 'unit' => 'kg', 'observation' => 'Masa dulce'],
                    ['name' => 'Mantequilla sin Sal', 'quantity' => 0.1250, 'unit' => 'kg', 'observation' => 'Fria'],
                    ['name' => 'Leche Condensada', 'quantity' => 2.0000, 'unit' => 'un', 'observation' => 'Relleno de limón'],
                    ['name' => 'Huevos de Gallina', 'quantity' => 4.0000, 'unit' => 'un', 'observation' => 'Yemas relleno, claras merengue'],
                    ['name' => 'Azúcar Granulada', 'quantity' => 0.1500, 'unit' => 'kg', 'observation' => 'Merengue'],
                ]
            ],
        ];

        foreach ($recipesData as $recipe) {
            $product = Product::where('name', $recipe['product'])->first();
            if (! $product) {
                continue;
            }

            $variant = ProductVariant::where('product_id', $product->id)
                ->where('name', $recipe['variant'])
                ->first();

            if (! $variant) {
                continue;
            }

            foreach ($recipe['ingredients'] as $ingredient) {
                $supply = Supply::where('name', $ingredient['name'])->first();
                if (! $supply) {
                    continue;
                }

                Recipe::updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'supply_id' => $supply->id,
                    ],
                    [
                        'quantity' => $ingredient['quantity'],
                        'unit' => $ingredient['unit'],
                        'observation' => $ingredient['observation'] !== '' ? $ingredient['observation'] : null,
                    ]
                );
            }
        }
    }
}
