<?php

namespace Modules\Banks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LiquidatorFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Banks\Models\LiquidatorFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

