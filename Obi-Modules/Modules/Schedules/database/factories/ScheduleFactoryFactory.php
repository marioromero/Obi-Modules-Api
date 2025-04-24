<?php

namespace Modules\Schedules\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Schedules\Models\ScheduleFactory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

