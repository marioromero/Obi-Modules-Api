<?php

namespace Modules\Cases\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AccidentTypeFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cases\Models\AccidentTypeFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

