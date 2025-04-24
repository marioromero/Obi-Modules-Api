<?php

namespace Modules\Banks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LossAdjusterFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Banks\Models\LossAdjusterFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

