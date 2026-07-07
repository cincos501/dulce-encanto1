<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tortas',
                'description' => 'Tortas artesanales y decoradas para celebraciones.',
                'is_active' => true,
            ],
            [
                'name' => 'Brazo Gitano',
                'description' => 'Brazos de reina con rellenos cremosos y suaves.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name']],
                [
                    'description' => $cat['description'],
                    'is_active' => $cat['is_active'],
                ]
            );
        }
    }
}
