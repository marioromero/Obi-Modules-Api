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
            'name'           => 'John',
            'lastname'       => 'Doe',
            'full_name'      => 'John Doe',
            'dni'            => '12345678-9',
            'username'       => 'johndoe',
            'password'       => bcrypt('password123'),
            'serial_number'  => '999.999.999',
            'email'          => 'johndoe@example.com',
            'address'        => '123 Main St',
            'phone'          => '987654321',
            'phone2'         => null,
            'gender'         => 'M',
            'marital_status' => 'Single',
            'occupation'     => 'Developer',
            'commune_id'     => null,
            'assigned_agent' => 1,
        ]);
    }
}
