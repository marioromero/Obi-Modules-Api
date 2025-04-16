<?php

namespace Modules\Users\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Users\Models\RoleFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

