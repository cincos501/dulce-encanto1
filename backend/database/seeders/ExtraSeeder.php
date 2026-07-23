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
            ['name' => 'Extra crema', 'description' => 'Porción extra de crema chantilly.'],
            ['name' => 'Nutella', 'description' => 'Adicional de Nutella cremosa.'],
            ['name' => 'Maní', 'description' => 'Maní tostado y picado.'],
            ['name' => 'Nuez', 'description' => 'Nueces frescas troceadas.'],
            ['name' => 'Chispas de chocolate', 'description' => 'Lluvia de chispas de chocolate de leche.'],
            ['name' => 'Velas', 'description' => 'Juego de velas de cumpleaños para decoración.'],
        ];

        foreach ($extras as $extra) {
            Extra::updateOrCreate(
                ['name' => $extra['name']],
                [
                    'description' => $extra['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
