<?php

namespace Modules\Customers\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Customers\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::create([
            'name' => 'John',
            'lastname' => 'Doe',
            'dni' => '12345678-9',
            'username' => 'johndoe',
            'password' => bcrypt('password123'),
            'email' => 'johndoe@example.com',
            'address' => '123 Main St',
            'phone' => '987654321',
            'phone2' => null,
            'gender' => 'M',
            'marital_status' => 'Single',
            'occupation' => 'Developer',
            'case_status_id' => null,
            'commune_id' => null,
            'user_id' => null,
        ]);
    }
}



