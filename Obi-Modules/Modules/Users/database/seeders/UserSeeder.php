<?php

namespace Modules\Users\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            User::create([
            'name' => 'Jane',
            'lastname' => 'Doe',
            'dni' => '87654321-0',
            'username' => 'janedoe',
            'password' => bcrypt('password123'),
            'email' => 'janedoe@example.com',
            'address' => '456 Elm St',
            'phone' => '123456789',
            'gender' => 'F',
            'status_id' => 1,
            'role_id' => 1,
            'commune_id' => null,
        ]);

        User::create([
            'name' => 'John',
            'lastname' => 'Smith',
            'dni' => '12345678-9',
            'username' => 'johnsmith',
            'password' => bcrypt('securepass'),
            'email' => 'johnsmith@example.com',
            'address' => '789 Oak Ave',
            'phone' => '987654321',
            'gender' => 'M',
            'status_id' => 1,
            'role_id' => 2,
            'commune_id' => null,
        ]);

        User::create([
            'name' => 'Laura',
            'lastname' => 'Martínez',
            'dni' => '11223344-5',
            'username' => 'lauram',
            'password' => bcrypt('mypassword'),
            'email' => 'laura.martinez@example.com',
            'address' => '321 Maple Rd',
            'phone' => '555123456',
            'gender' => 'F',
            'status_id' => 1,
            'role_id' => 3,
            'commune_id' => null,
        ]);

        User::create([
            'name' => 'Carlos',
            'lastname' => 'González',
            'dni' => '99887766-1',
            'username' => 'carlosg',
            'password' => bcrypt('passcarlos'),
            'email' => 'carlos.g@example.com',
            'address' => '654 Pine Blvd',
            'phone' => '444987654',
            'gender' => 'M',
            'status_id' => 1,
            'role_id' => 2,
            'commune_id' => null,
        ]);

        User::create([
            'name' => 'Ana',
            'lastname' => 'Rojas',
            'dni' => '55667788-2',
            'username' => 'anarojas',
            'password' => bcrypt('anapass'),
            'email' => 'ana.rojas@example.com',
            'address' => '987 Cedar Ln',
            'phone' => '333456789',
            'gender' => 'F',
            'status_id' => 1,
            'role_id' => 1,
            'commune_id' => null,
        ]);
    }
}



