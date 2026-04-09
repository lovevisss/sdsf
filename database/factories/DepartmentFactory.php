<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = ['计算机科学系', '数学与应用数学系', '物理系', '化学系', '生物系', '外语系', '管理系'];

        return [
            'name' => $this->faker->unique()->randomElement($names),
            'code' => $this->faker->unique()->numerify('DEPT###'),
            'description' => $this->faker->sentence(),
        ];
    }
}
