<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear Spatie permission cache to prevent stale memory states
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Define all permissions
        $permissionsByModule = [
            'users' => [
                'users.manage' => 'Administrar usuarios del sistema',
            ],
            'categories' => [
                'categories.view' => 'Ver categorías de productos',
                'categories.create' => 'Crear nuevas categorías',
                'categories.update' => 'Editar categorías existentes',
                'categories.delete' => 'Eliminar categorías',
            ],
            'products' => [
                'products.view' => 'Ver catálogo de productos',
                'products.create' => 'Crear nuevos productos',
                'products.update' => 'Editar productos existentes',
                'products.delete' => 'Eliminar productos',
            ],
            'product_variants' => [
                'product_variants.view' => 'Ver variantes de productos',
                'product_variants.create' => 'Crear nuevas variantes',
                'product_variants.update' => 'Editar variantes existentes',
                'product_variants.delete' => 'Eliminar variantes',
            ],
            'extras' => [
                'extras.view' => 'Ver adicionales/extras',
                'extras.create' => 'Crear nuevos extras',
                'extras.update' => 'Editar extras existentes',
                'extras.delete' => 'Eliminar extras',
            ],
            'supplies' => [
                'supplies.view' => 'Ver insumos y materias primas',
                'supplies.create' => 'Registrar nuevos insumos',
                'supplies.update' => 'Editar insumos existentes',
                'supplies.delete' => 'Eliminar insumos',
            ],
            'suppliers' => [
                'suppliers.view' => 'Ver lista de proveedores',
                'suppliers.create' => 'Registrar nuevos proveedores',
                'suppliers.update' => 'Editar proveedores existentes',
                'suppliers.delete' => 'Eliminar proveedores',
            ],
            'recipes' => [
                'recipes.view' => 'Ver recetas de producción',
                'recipes.create' => 'Registrar nuevas recetas',
                'recipes.update' => 'Editar recetas existentes',
                'recipes.delete' => 'Eliminar recetas',
            ],
            'customers' => [
                'customers.view' => 'Ver base de clientes',
                'customers.create' => 'Registrar nuevos clientes',
                'customers.update' => 'Editar clientes existentes',
            ],
            'orders' => [
                'orders.view' => 'Ver pedidos y ventas',
                'orders.create' => 'Registrar nuevos pedidos',
                'orders.update' => 'Actualizar estado de pedidos',
                'orders.delete' => 'Cancelar/Eliminar pedidos',
            ],
            'promotions' => [
                'promotions.view' => 'Ver promociones y ofertas',
                'promotions.create' => 'Crear nuevas promociones',
                'promotions.update' => 'Editar promociones existentes',
                'promotions.delete' => 'Eliminar promociones',
            ],
        ];

        // Create all permissions (idempotent setup)
        $allCreatedPermissions = [];
        foreach ($permissionsByModule as $module => $perms) {
            foreach ($perms as $name => $description) {
                $allCreatedPermissions[$name] = Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['description' => $description]
                );
            }
        }

        // 2. Fetch existing roles
        $adminRole = Role::where('name', 'Administrador')->first();
        $reposteroRole = Role::where('name', 'Repostero')->first();
        $operacionesRole = Role::where('name', 'Encargado de Operaciones y Suministros')->first();
        $comercialRole = Role::where('name', 'Encargado Comercial')->first();

        // 3. Assign permissions to roles

        // Administrador: gets all permissions
        if ($adminRole) {
            $adminRole->syncPermissions(array_keys($allCreatedPermissions));
        }

        // Repostero: recipes, supplies view, products view, extras view, variant view
        if ($reposteroRole) {
            $reposteroRole->syncPermissions([
                'recipes.view',
                'recipes.create',
                'recipes.update',
                'recipes.delete',
                'supplies.view',
                'products.view',
                'extras.view',
                'product_variants.view',
            ]);
        }

        // Encargado de Operaciones y Suministros: supplies, suppliers
        if ($operacionesRole) {
            $operacionesRole->syncPermissions([
                'supplies.view',
                'supplies.create',
                'supplies.update',
                'supplies.delete',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
            ]);
        }

        // Encargado Comercial: categories, products, promotions, extras, variants, orders, customers
        if ($comercialRole) {
            $comercialRole->syncPermissions([
                'categories.view',
                'categories.create',
                'categories.update',
                'categories.delete',
                'products.view',
                'products.create',
                'products.update',
                'products.delete',
                'promotions.view',
                'promotions.create',
                'promotions.update',
                'promotions.delete',
                'extras.view',
                'extras.create',
                'extras.update',
                'extras.delete',
                'product_variants.view',
                'product_variants.create',
                'product_variants.update',
                'product_variants.delete',
                'orders.view',
                'orders.create',
                'orders.update',
                'orders.delete',
                'customers.view',
                'customers.create',
                'customers.update',
            ]);
        }
    }
}
