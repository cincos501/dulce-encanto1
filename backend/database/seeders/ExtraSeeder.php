<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Extra;
use Illuminate\Database\Seeder;

class ExtraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $extras = [
            ['name' => 'Extra crema', 'price' => 1.00, 'description' => 'Porción extra de crema chantilly.'],
            ['name' => 'Nutella', 'price' => 2.00, 'description' => 'Adicional de Nutella cremosa.'],
            ['name' => 'Maní', 'price' => 0.80, 'description' => 'Maní tostado y picado.'],
            ['name' => 'Nuez', 'price' => 1.20, 'description' => 'Nueces frescas troceadas.'],
            ['name' => 'Chispas de chocolate', 'price' => 0.70, 'description' => 'Lluvia de chispas de chocolate de leche.'],
            ['name' => 'Velas', 'price' => 1.50, 'description' => 'Juego de velas de cumpleaños para decoración.'],
        ];

        foreach ($extras as $extra) {
            Extra::updateOrCreate(
                ['name' => $extra['name']],
                [
                    'price' => $extra['price'],
                    'description' => $extra['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
