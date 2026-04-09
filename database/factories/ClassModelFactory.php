<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassModel>
 */
class ClassModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->numerify('##级##班'),
            'department_id' => Department::factory(),
            'grade' => $this->faker->randomElement(['2024级', '2025级', '2026级']),
            'description' => $this->faker->sentence(),
        ];
    }
}
