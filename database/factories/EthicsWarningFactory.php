<?php

namespace Database\Factories;

use App\Models\EthicsProfile;
use App\Models\EthicsWarning;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthicsWarning>
 */
class EthicsWarningFactory extends Factory
{
    protected $model = EthicsWarning::class;

    public function definition(): array
    {
        return [
            'ethics_profile_id' => EthicsProfile::factory(),
            'assignee_id' => User::factory(),
            'warning_level' => $this->faker->randomElement(['yellow', 'orange', 'red']),
            'source_type' => $this->faker->randomElement(['teaching', 'research', 'behavior', 'training', 'manual']),
            'reason' => $this->faker->sentence(),
            'status' => 'open',
            'detected_at' => now(),
            'closed_at' => null,
        ];
    }
}

