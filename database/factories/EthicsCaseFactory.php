<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\EthicsCase;
use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthicsCase>
 */
class EthicsCaseFactory extends Factory
{
    protected $model = EthicsCase::class;

    public function definition(): array
    {
        return [
            'ethics_profile_id' => EthicsProfile::factory(),
            'reporter_id' => User::factory(),
            'department_id' => Department::factory(),
            'channel' => $this->faker->randomElement(['pc', 'mobile', 'wechat', 'wecom', 'other']),
            'is_anonymous' => false,
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraph(),
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            'status' => 'reported',
            'reported_at' => now(),
            'accepted_at' => null,
            'closed_at' => null,
        ];
    }
}

