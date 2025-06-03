<?php

namespace Modules\Mailing\database\seeders;

use Illuminate\Database\Seeder;

class MailingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $this->call([
            DepartmentSeeder::class,
            EmailTemplateSeeder::class,
            CustomersSetSeeder::class,
            CustomerDetailSeeder::class,
            EmailScheduleSeeder::class,
        ]);
    }
}



