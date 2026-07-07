<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrador',
                'description' => 'Acceso total al sistema y configuraciones generales.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Repostero',
                'description' => 'Gestión de recetas, insumos, producción y preparación de pedidos.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Encargado de Operaciones y Suministros',
                'description' => 'Gestión de inventario de insumos, abastecimiento y proveedores.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Encargado Comercial',
                'description' => 'Gestión de catálogo de productos, categorías, promociones y ventas.',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                ['description' => $roleData['description']]
            );
        }
    }
}
