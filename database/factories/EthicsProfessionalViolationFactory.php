<?php

namespace Database\Factories;

use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EthicsProfessionalViolation>
 */
class EthicsProfessionalViolationFactory extends Factory
{
    protected $model = EthicsProfessionalViolation::class;

    public function definition(): array
    {
        return [
            'ethics_profile_id' => EthicsProfile::factory(),
            'violator_user_id' => User::factory(),
            'recorder_user_id' => User::factory(),
            'staff_no' => 'T'.$this->faker->unique()->numberBetween(1000, 9999),
            'staff_name' => $this->faker->name(),
            'staff_unit_name' => $this->faker->word().'学院',
            'violation_type' => $this->faker->numberBetween(23, 34),
            'violation_at' => now()->subDays($this->faker->numberBetween(0, 90)),
            'deduction_points' => $this->faker->randomFloat(2, 0.5, 8),
            'notes' => $this->faker->sentence(),
        ];
    }
}
