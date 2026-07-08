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
        $cupcakes = Category::where('name', 'Cupcakes')->first();
        $galletas = Category::where('name', 'Galletas')->first();
        $postres = Category::where('name', 'Postres')->first();

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

        if ($cupcakes) {
            Product::updateOrCreate(
                ['name' => 'Cupcake de Vainilla'],
                [
                    'category_id' => $cupcakes->id,
                    'description' => 'Delicado cupcake de vainilla con frosting de crema de mantequilla.',
                    'is_active' => true,
                ]
            );

            Product::updateOrCreate(
                ['name' => 'Cupcake de Red Velvet'],
                [
                    'category_id' => $cupcakes->id,
                    'description' => 'Cupcake clásico aterciopelado rojo con frosting de queso crema.',
                    'is_active' => true,
                ]
            );
        }

        if ($galletas) {
            Product::updateOrCreate(
                ['name' => 'Galletas de Chispas'],
                [
                    'category_id' => $galletas->id,
                    'description' => 'Galletas suaves y masticables cargadas con abundantes chispas de chocolate.',
                    'is_active' => true,
                ]
            );

            Product::updateOrCreate(
                ['name' => 'Galletas de Avena y Miel'],
                [
                    'category_id' => $galletas->id,
                    'description' => 'Saludables y deliciosas galletas de avena endulzadas con miel natural.',
                    'is_active' => true,
                ]
            );
        }

        if ($postres) {
            Product::updateOrCreate(
                ['name' => 'Cheesecake de Frutilla'],
                [
                    'category_id' => $postres->id,
                    'description' => 'Crema de queso suave sobre base crujiente de galleta y cobertura de frutillas.',
                    'is_active' => true,
                ]
            );

            Product::updateOrCreate(
                ['name' => 'Pie de Limón'],
                [
                    'category_id' => $postres->id,
                    'description' => 'Base crujiente rellena de crema de limón ácida y decorada con merengue dorado.',
                    'is_active' => true,
                ]
            );
        }
    }
}
