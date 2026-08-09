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
                'business_name' => 'Distribuidora Pil Bolivia',
                'phone' => '59170012341',
                'email' => 'ventas@pil.com.bo',
                'address' => 'Av. Blanco Galindo Km 7, Cochabamba',
            ],
            [
                'business_name' => 'Molino La Estancia',
                'phone' => '59170012342',
                'email' => 'contacto@laestancia.com.bo',
                'address' => 'Zona Industrial, Santa Cruz',
            ],
            [
                'business_name' => 'Comercializadora Fidalga',
                'phone' => '59170012343',
                'email' => 'soporte@fidalga.com.bo',
                'address' => 'Av. Banzer entre 2do y 3er anillo, Santa Cruz',
            ],
            [
                'business_name' => 'Insumos Pasteleros del Oriente',
                'phone' => '59170012344',
                'email' => 'pedidos@insumosoriente.com.bo',
                'address' => 'Calle Arenales 456, Santa Cruz',
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
