<?php

namespace Modules\Core\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SoftdeleteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Core\Models\Softdelete::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

