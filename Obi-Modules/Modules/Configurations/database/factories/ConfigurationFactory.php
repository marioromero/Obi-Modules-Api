<?php

namespace Modules\Configurations\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConfigurationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Configurations\Models\Configuration::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

