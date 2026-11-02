<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Role
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Manager']);
        Role::create(['name' => 'Notaris']);

        // Buat Akun Admin
        $admin = User::create([
            'name' => 'Pak Admin',
            'email' => 'admin@notaris.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('Admin');

        // Buat Akun Manager
        $manager = User::create([
            'name' => 'Pak Manager',
            'email' => 'manager@notaris.com',
            'password' => Hash::make('password123'),
        ]);
        $manager->assignRole('Manager');

        // Buat Akun Notaris
        $notaris = User::create([
            'name' => 'Ibu Notaris',
            'email' => 'notaris@notaris.com',
            'password' => Hash::make('password123'),
        ]);
        $notaris->assignRole('Notaris');
    }
}
