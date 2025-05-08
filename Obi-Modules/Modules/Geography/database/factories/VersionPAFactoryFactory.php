<?php

namespace Modules\Geography\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VersionPAFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Geography\Models\VersionPAFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

