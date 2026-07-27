<?php

namespace Database\Factories;

use App\Models\ContentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentStatus>
 */
class ContentStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->word(),
        ];
    }
}
