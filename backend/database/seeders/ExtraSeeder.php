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
            ['name' => 'Nuez', 'price' => 1.50, 'description' => 'Nueces picadas crujientes.'],
            ['name' => 'Chantilly', 'price' => 1.00, 'description' => 'Porción extra de crema chantilly.'],
            ['name' => 'Frutilla', 'price' => 1.25, 'description' => 'Frutillas frescas fileteadas.'],
            ['name' => 'Chocolate extra', 'price' => 1.50, 'description' => 'Fudge de chocolate adicional.'],
            ['name' => 'Mensaje personalizado', 'price' => 2.00, 'description' => 'Dedicatoria escrita sobre placa de chocolate.'],
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
