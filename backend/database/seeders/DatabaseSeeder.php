<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            TestUsersSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            ExtraSeeder::class,
        ]);

        // Programmatic validation step
        $this->verifySeeding();
    }

    /**
     * Verify database seeding state programmatically.
     *
     * @throws \RuntimeException
     */
    protected function verifySeeding(): void
    {
        // 1. Roles check
        $expectedRoles = [
            'Administrador',
            'Repostero',
            'Encargado de Operaciones y Suministros',
            'Encargado Comercial',
        ];

        foreach ($expectedRoles as $roleName) {
            if (! Role::where('name', $roleName)->exists()) {
                throw new \RuntimeException("Verificación fallida: El rol {$roleName} no existe en la base de datos.");
            }
        }

        if (Role::count() !== count($expectedRoles)) {
            throw new \RuntimeException('Verificación fallida: Se encontraron roles adicionales o duplicados.');
        }

        // 2. Permission users.manage check
        if (! Permission::where('name', 'users.manage')->exists()) {
            throw new \RuntimeException("Verificación fallida: El permiso 'users.manage' no existe.");
        }

        // 3. Admin role permission assignment
        $adminRole = Role::where('name', 'Administrador')->first();
        if ($adminRole === null || ! $adminRole->hasPermissionTo('users.manage')) {
            throw new \RuntimeException("Verificación fallida: El rol Administrador no posee el permiso 'users.manage'.");
        }

        // 4. Test Users check
        $expectedUsers = [
            'admin@dulceencanto.com' => 'Administrador',
            'repostero@dulceencanto.com' => 'Repostero',
            'operaciones@dulceencanto.com' => 'Encargado de Operaciones y Suministros',
            'comercial@dulceencanto.com' => 'Encargado Comercial',
        ];

        foreach ($expectedUsers as $email => $roleName) {
            $user = User::where('email', $email)->first();
            if ($user === null) {
                throw new \RuntimeException("Verificación fallida: El usuario {$email} no existe.");
            }
            if (! $user->is_active) {
                throw new \RuntimeException("Verificación fallida: El usuario {$email} no está activo.");
            }
            if (! $user->hasRole($roleName)) {
                throw new \RuntimeException("Verificación fallida: El usuario {$email} no tiene asignado el rol {$roleName}.");
            }
            if ($user->roles()->count() !== 1) {
                throw new \RuntimeException("Verificación fallida: El usuario {$email} no tiene exactamente un rol.");
            }
        }

        // 5. Total users check (exactly 4 users seeded)
        if (User::count() !== count($expectedUsers)) {
            throw new \RuntimeException('Verificación fallida: Cantidad inesperada de usuarios en la base de datos.');
        }
    }
}
