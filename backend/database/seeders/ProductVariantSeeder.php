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
                ['name' => 'Porción', 'base_price' => 3.50],
                ['name' => 'Torta completa', 'base_price' => 25.00],
            ],
            'Torta Tres Leches' => [
                ['name' => 'Porción', 'base_price' => 3.80],
                ['name' => 'Torta completa', 'base_price' => 28.00],
            ],
            'Cupcake de Vainilla' => [
                ['name' => 'Unidad', 'base_price' => 2.00],
                ['name' => 'Caja x6', 'base_price' => 10.00],
            ],
            'Cupcake de Red Velvet' => [
                ['name' => 'Unidad', 'base_price' => 2.50],
                ['name' => 'Caja x6', 'base_price' => 12.00],
            ],
            'Galletas de Chispas' => [
                ['name' => 'Unidad', 'base_price' => 1.50],
                ['name' => 'Paquete x6', 'base_price' => 7.00],
            ],
            'Galletas de Avena y Miel' => [
                ['name' => 'Unidad', 'base_price' => 1.80],
                ['name' => 'Paquete x6', 'base_price' => 8.50],
            ],
            'Cheesecake de Frutilla' => [
                ['name' => 'Porción', 'base_price' => 4.00],
                ['name' => 'Entero', 'base_price' => 32.00],
            ],
            'Pie de Limón' => [
                ['name' => 'Porción', 'base_price' => 3.50],
                ['name' => 'Entero', 'base_price' => 28.00],
            ],
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
                    'base_price' => $var['base_price'],
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
                        $syncData[$extra->id] = ['extra_price' => $extra->price];
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
