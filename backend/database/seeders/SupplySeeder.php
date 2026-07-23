<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\Supply;
use Illuminate\Database\Seeder;

class SupplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supplies = [
            ['name' => 'Harina de Trigo', 'unit' => 'kg', 'stock' => 150.0000, 'minimum_stock' => 30.0000, 'average_cost' => 1.20, 'supplier' => 'Molino Linderos', 'price' => 1.10],
            ['name' => 'Azúcar Granulada', 'unit' => 'kg', 'stock' => 100.0000, 'minimum_stock' => 20.0000, 'average_cost' => 1.50, 'supplier' => 'Comercializadora Alvi', 'price' => 1.40],
            ['name' => 'Mantequilla sin Sal', 'unit' => 'kg', 'stock' => 50.0000, 'minimum_stock' => 10.0000, 'average_cost' => 6.50, 'supplier' => 'Distribuidora Soprole', 'price' => 6.20],
            ['name' => 'Crema de Leche', 'unit' => 'lt', 'stock' => 60.0000, 'minimum_stock' => 15.0000, 'average_cost' => 4.20, 'supplier' => 'Distribuidora Soprole', 'price' => 4.00],
            ['name' => 'Chocolate Amargo 60%', 'unit' => 'kg', 'stock' => 30.0000, 'minimum_stock' => 5.0000, 'average_cost' => 12.00, 'supplier' => 'Insumos Pasteleros del Sur', 'price' => 11.50],
            ['name' => 'Chocolate de Leche', 'unit' => 'kg', 'stock' => 25.0000, 'minimum_stock' => 5.0000, 'average_cost' => 10.50, 'supplier' => 'Insumos Pasteleros del Sur', 'price' => 10.00],
            ['name' => 'Esencia de Vainilla', 'unit' => 'lt', 'stock' => 5.0000, 'minimum_stock' => 1.0000, 'average_cost' => 15.00, 'supplier' => 'Insumos Pasteleros del Sur', 'price' => 14.50],
            ['name' => 'Polvo de Hornear', 'unit' => 'kg', 'stock' => 10.0000, 'minimum_stock' => 2.0000, 'average_cost' => 3.80, 'supplier' => 'Comercializadora Alvi', 'price' => 3.60],
            ['name' => 'Huevos de Gallina', 'unit' => 'un', 'stock' => 360.0000, 'minimum_stock' => 60.0000, 'average_cost' => 0.15, 'supplier' => 'Comercializadora Alvi', 'price' => 0.13],
            ['name' => 'Sal Fina', 'unit' => 'kg', 'stock' => 5.0000, 'minimum_stock' => 1.0000, 'average_cost' => 0.80, 'supplier' => 'Comercializadora Alvi', 'price' => 0.75],
            ['name' => 'Leche Entera', 'unit' => 'lt', 'stock' => 80.0000, 'minimum_stock' => 20.0000, 'average_cost' => 1.10, 'supplier' => 'Distribuidora Soprole', 'price' => 1.05],
            ['name' => 'Leche Condensada', 'unit' => 'un', 'stock' => 48.0000, 'minimum_stock' => 12.0000, 'average_cost' => 1.80, 'supplier' => 'Distribuidora Soprole', 'price' => 1.70],
            ['name' => 'Manjar / Dulce de Leche', 'unit' => 'kg', 'stock' => 40.0000, 'minimum_stock' => 10.0000, 'average_cost' => 3.50, 'supplier' => 'Distribuidora Soprole', 'price' => 3.30],
            ['name' => 'Frambuesas Congeladas', 'unit' => 'kg', 'stock' => 15.0000, 'minimum_stock' => 3.0000, 'average_cost' => 8.50, 'supplier' => 'Insumos Pasteleros del Sur', 'price' => 8.00],
            ['name' => 'Frutillas Frescas', 'unit' => 'kg', 'stock' => 20.0000, 'minimum_stock' => 5.0000, 'average_cost' => 4.00, 'supplier' => 'Comercializadora Alvi', 'price' => 3.80],
            ['name' => 'Queso Crema', 'unit' => 'kg', 'stock' => 35.0000, 'minimum_stock' => 8.0000, 'average_cost' => 7.20, 'supplier' => 'Distribuidora Soprole', 'price' => 6.90],
            ['name' => 'Cacao en Polvo', 'unit' => 'kg', 'stock' => 12.0000, 'minimum_stock' => 3.0000, 'average_cost' => 9.00, 'supplier' => 'Insumos Pasteleros del Sur', 'price' => 8.50],
            ['name' => 'Gelatina sin Sabor', 'unit' => 'kg', 'stock' => 8.0000, 'minimum_stock' => 2.0000, 'average_cost' => 14.00, 'supplier' => 'Comercializadora Alvi', 'price' => 13.50],
            ['name' => 'Nueces Mariposa', 'unit' => 'kg', 'stock' => 15.0000, 'minimum_stock' => 3.0000, 'average_cost' => 13.00, 'supplier' => 'Insumos Pasteleros del Sur', 'price' => 12.50],
            ['name' => 'Almidón de Maíz', 'unit' => 'kg', 'stock' => 15.0000, 'minimum_stock' => 3.0000, 'average_cost' => 2.50, 'supplier' => 'Comercializadora Alvi', 'price' => 2.30],
        ];

        foreach ($supplies as $item) {
            $supply = Supply::updateOrCreate(
                ['name' => $item['name']],
                [
                    'unit' => $item['unit'],
                    'stock' => $item['stock'],
                    'minimum_stock' => $item['minimum_stock'],
                    'average_cost' => $item['average_cost'],
                    'is_active' => true,
                ]
            );

            // Link to the specified supplier
            $supplier = Supplier::where('business_name', $item['supplier'])->first();
            if ($supplier) {
                $supply->suppliers()->syncWithoutDetaching([
                    $supplier->id => ['purchase_price' => $item['price']]
                ]);
            }
        }
    }
}
