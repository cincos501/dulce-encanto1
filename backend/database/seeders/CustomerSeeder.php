<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            ['full_name' => 'María José Soto', 'phone' => '+56911112222', 'email' => 'mariajose@example.com'],
            ['full_name' => 'Juan Carlos Perez', 'phone' => '+56933334444', 'email' => 'juanperez@example.com'],
            ['full_name' => 'Sofía Camila Castro', 'phone' => '+56955556666', 'email' => 'sofiacastro@example.com'],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['phone' => $customer['phone']],
                [
                    'full_name' => $customer['full_name'],
                    'email' => $customer['email'],
                ]
            );
        }
    }
}
