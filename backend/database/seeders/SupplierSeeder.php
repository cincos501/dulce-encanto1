<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'business_name' => 'Distribuidora Soprole',
                'phone' => '+56912345678',
                'email' => 'ventas@soprole.cl',
                'address' => 'Av. Vitacura 4400, Santiago',
            ],
            [
                'business_name' => 'Molino Linderos',
                'phone' => '+56987654321',
                'email' => 'contacto@linderos.cl',
                'address' => 'Camino Linderos S/N, Buin',
            ],
            [
                'business_name' => 'Comercializadora Alvi',
                'phone' => '+56223456789',
                'email' => 'soporte@alvi.cl',
                'address' => 'Av. Américo Vespucio 1500, Pudahuel',
            ],
            [
                'business_name' => 'Insumos Pasteleros del Sur',
                'phone' => '+56944445555',
                'email' => 'pedidos@insumospasteleros.cl',
                'address' => 'Calle Los Aromos 123, Temuco',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['business_name' => $supplier['business_name']],
                [
                    'phone' => $supplier['phone'],
                    'email' => $supplier['email'],
                    'address' => $supplier['address'],
                    'is_active' => true,
                ]
            );
        }
    }
}
