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

    }
}



