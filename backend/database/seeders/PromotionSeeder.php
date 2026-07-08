<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promo1 = Promotion::updateOrCreate(
            ['name' => 'Descuento Especial del 15%'],
            [
                'description' => 'Disfruta de un 15% de descuento en presentaciones seleccionadas.',
                'discount_type' => 'percentage',
                'discount' => 15.00,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonth(),
                'is_active' => true,
            ]
        );

        $promo2 = Promotion::updateOrCreate(
            ['name' => 'Descuento Fijo de Temporada'],
            [
                'description' => 'Descuento fijo de $3.00 en nuestras delicias destacadas.',
                'discount_type' => 'fixed',
                'discount' => 3.00,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonth(),
                'is_active' => true,
            ]
        );

        // Fetch all product variants
        $variants = ProductVariant::all();

        if ($variants->isNotEmpty()) {
            // Associate Promotion 1 to the first 6 variants
            $promo1->variants()->sync($variants->take(6)->pluck('id')->toArray());

            // Associate Promotion 2 to the next 6 variants
            $promo2->variants()->sync($variants->skip(6)->take(6)->pluck('id')->toArray());
        }
    }
}
