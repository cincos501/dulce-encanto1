<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $variantsData = [
            'Torta Selva Negra' => [
                ['name' => 'Personal', 'base_price' => 5.50],
                ['name' => '8 porciones', 'base_price' => 20.00],
            ],
            'Torta Tres Leches' => [
                ['name' => 'Personal', 'base_price' => 6.00],
                ['name' => '12 porciones', 'base_price' => 24.50],
            ],
            'Brazo Gitano de Chocolate' => [
                ['name' => 'Pequeño', 'base_price' => 8.00],
                ['name' => 'Grande', 'base_price' => 15.00],
            ],
            'Brazo Gitano de Frutilla' => [
                ['name' => 'Pequeño', 'base_price' => 7.50],
                ['name' => 'Grande', 'base_price' => 14.50],
            ],
        ];

        foreach ($variantsData as $productName => $variants) {
            $product = Product::where('name', $productName)->first();
            if (! $product) {
                continue;
            }

            foreach ($variants as $var) {
                // To maintain idempotency, search by product_id and name
                $existing = ProductVariant::where('product_id', $product->id)
                    ->where('name', $var['name'])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'base_price' => $var['base_price'],
                        'is_active' => true,
                    ]);
                } else {
                    $sku = $this->generateUniqueSku($productName, $var['name']);
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $var['name'],
                        'sku' => $sku,
                        'base_price' => $var['base_price'],
                        'is_active' => true,
                    ]);
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
