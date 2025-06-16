<?php

namespace Modules\Cases\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AgreementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cases\Models\Agreement::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

