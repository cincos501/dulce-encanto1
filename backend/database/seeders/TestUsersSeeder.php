<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usersData = [
            [
                'full_name' => 'Administrador Sistema',
                'email' => 'admin@dulceencanto.com',
                'password' => 'password',
                'phone' => '+56911111111',
                'role' => 'Administrador',
            ],
            [
                'full_name' => 'Repostero Principal',
                'email' => 'repostero@dulceencanto.com',
                'password' => 'password',
                'phone' => '+56922222222',
                'role' => 'Repostero',
            ],
            [
                'full_name' => 'Encargado de Operaciones',
                'email' => 'operaciones@dulceencanto.com',
                'password' => 'password',
                'phone' => '+56933333333',
                'role' => 'Encargado de Operaciones y Suministros',
            ],
            [
                'full_name' => 'Encargado Comercial',
                'email' => 'comercial@dulceencanto.com',
                'password' => 'password',
                'phone' => '+56944444444',
                'role' => 'Encargado Comercial',
            ],
        ];

        foreach ($usersData as $data) {
            $user = User::where('email', $data['email'])->first();

            if ($user) {
                $user->update([
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'is_active' => true,
                ]);
            } else {
                $user = User::create([
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'phone' => $data['phone'],
                    'is_active' => true,
                ]);
            }

            $roleObj = Role::where('name', $data['role'])->first();
            if ($roleObj && ! $user->hasRole($data['role'])) {
                // Snycronize roles so they only have this one role assigned
                $user->syncRoles([$roleObj]);
            }
        }
    }
}
