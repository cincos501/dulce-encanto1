<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tortas = Category::where('name', 'Tortas')->first();
        $brazos = Category::where('name', 'Brazo Gitano')->first();

        if ($tortas) {
            Product::updateOrCreate(
                ['name' => 'Torta Selva Negra'],
                [
                    'category_id' => $tortas->id,
                    'description' => 'Clásico bizcocho de chocolate relleno de cerezas y crema chantilly.',
                    'is_active' => true,
                ]
            );

            Product::updateOrCreate(
                ['name' => 'Torta Tres Leches'],
                [
                    'category_id' => $tortas->id,
                    'description' => 'Esponjoso bizcocho bañado en tres tipos de leche y decorado con merengue.',
                    'is_active' => true,
                ]
            );
        }

        if ($brazos) {
            Product::updateOrCreate(
                ['name' => 'Brazo Gitano de Chocolate'],
                [
                    'category_id' => $brazos->id,
                    'description' => 'Bizcochuelo enrollado relleno de ganache de chocolate belga.',
                    'is_active' => true,
                ]
            );

            Product::updateOrCreate(
                ['name' => 'Brazo Gitano de Frutilla'],
                [
                    'category_id' => $brazos->id,
                    'description' => 'Bizcochuelo enrollado relleno de crema y frutillas frescas.',
                    'is_active' => true,
                ]
            );
        }
    }
}
