<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Extra;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $variantsMap = [
            'Torta Selva Negra' => [
                ['name' => 'Porción', 'price' => 3.50, 'serves_people' => 1],
                ['name' => 'Torta completa', 'price' => 25.00, 'serves_people' => 20],
            ],
            'Torta Tres Leches' => [
                ['name' => 'Porción', 'price' => 3.80, 'serves_people' => 1],
                ['name' => 'Torta completa', 'price' => 28.00, 'serves_people' => 20],
            ],
            'Cupcake de Vainilla' => [
                ['name' => 'Unidad', 'price' => 2.00, 'serves_people' => 1],
                ['name' => 'Caja x6', 'price' => 10.00, 'serves_people' => 6],
            ],
            'Cupcake de Red Velvet' => [
                ['name' => 'Unidad', 'price' => 2.50, 'serves_people' => 1],
                ['name' => 'Caja x6', 'price' => 12.00, 'serves_people' => 6],
            ],
            'Galletas de Chispas' => [
                ['name' => 'Unidad', 'price' => 1.50, 'serves_people' => 1],
                ['name' => 'Paquete x6', 'price' => 7.00, 'serves_people' => 6],
            ],
            'Galletas de Avena y Miel' => [
                ['name' => 'Unidad', 'price' => 1.80, 'serves_people' => 1],
                ['name' => 'Paquete x6', 'price' => 8.50, 'serves_people' => 6],
            ],
            'Cheesecake de Frutilla' => [
                ['name' => 'Porción', 'price' => 4.00, 'serves_people' => 1],
                ['name' => 'Entero', 'price' => 32.00, 'serves_people' => 16],
            ],
            'Pie de Limón' => [
                ['name' => 'Porción', 'price' => 3.50, 'serves_people' => 1],
                ['name' => 'Entero', 'price' => 28.00, 'serves_people' => 16],
            ],
        ];

        $extraPrices = [
            'Extra crema' => 1.00,
            'Nutella' => 2.00,
            'Maní' => 0.80,
            'Nuez' => 1.20,
            'Chispas de chocolate' => 0.70,
            'Velas' => 1.50,
        ];

        $extras = Extra::all();

        foreach ($variantsMap as $productName => $variants) {
            $product = Product::where('name', $productName)->first();
            if (! $product) {
                continue;
            }

            foreach ($variants as $var) {
                $sku = $this->generateUniqueSku($productName, $var['name']);

                // Create the variant
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $var['name'],
                    'sku' => $sku,
                    'price' => $var['price'],
                    'serves_people' => $var['serves_people'],
                    'is_active' => true,
                ]);

                // Create 1 primary image for the variant
                // Use a clean and valid placeholder image
                $variant->images()->create([
                    'image_url' => 'https://placehold.co/600x600?text=' . urlencode($product->name . ' (' . $variant->name . ')'),
                    'is_primary' => true,
                ]);

                // Snychronize 2-3 random extras
                if ($extras->isNotEmpty()) {
                    $randomExtras = $extras->random(rand(2, 3));
                    $syncData = [];
                    foreach ($randomExtras as $extra) {
                        $p = $extraPrices[$extra->name] ?? 1.00;
                        $syncData[$extra->id] = ['price' => $p];
                    }
                    $variant->extras()->sync($syncData);
                }
            }
        }
    }

    /**
     * Generate unique SKU mirroring ProductVariantService logic.
     */
    private function generateUniqueSku(string $productName, string $variantName): string
    {
        // Strip non-alphanumeric and uppercase
        $cleanProduct = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $productName));
        $cleanVariant = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $variantName));

        $prefix = substr($cleanProduct, 0, 4).'-'.substr($cleanVariant, 0, 4);

        $sku = $prefix;
        $counter = 1;
        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = $prefix.'-'.str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }
}
