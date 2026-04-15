<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthicsProfile>
 */
class EthicsProfileFactory extends Factory
{
    protected $model = EthicsProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'staff_no' => $this->faker->unique()->numerify('T########'),
            'department_id' => Department::factory(),
            'position' => $this->faker->randomElement(['教授', '副教授', '讲师', '辅导员']),
            'identity_type' => $this->faker->randomElement(['专任教师', '辅导员', '实验技术人员', '行政人员']),
            'hired_at' => $this->faker->date(),
            'status' => 'active',
            'metadata' => [],
            'last_synced_at' => now(),
        ];
    }
}

