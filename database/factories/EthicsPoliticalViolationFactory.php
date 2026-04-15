<?php

namespace Database\Factories;

use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthicsPoliticalViolation>
 */
class EthicsPoliticalViolationFactory extends Factory
{
    protected $model = EthicsPoliticalViolation::class;

    public function definition(): array
    {
        return [
            'ethics_profile_id' => EthicsProfile::factory(),
            'violator_user_id' => User::factory(),
            'recorder_user_id' => User::factory(),
            'violation_type' => $this->faker->numberBetween(1, 7),
            'violation_at' => now()->subDays($this->faker->numberBetween(0, 90)),
            'deduction_points' => $this->faker->randomFloat(2, 0.5, 8),
            'notes' => $this->faker->sentence(),
        ];
    }
}

