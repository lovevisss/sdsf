<?php

namespace Database\Factories;

use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthicsDisciplineViolation>
 */
class EthicsDisciplineViolationFactory extends Factory
{
    protected $model = EthicsDisciplineViolation::class;

    public function definition(): array
    {
        $severity = $this->faker->randomElement(['A', 'B', 'C']);

        return [
            'ethics_profile_id' => EthicsProfile::factory(),
            'violator_user_id' => User::factory(),
            'recorder_user_id' => User::factory(),
            'staff_no' => 'D'.$this->faker->unique()->numberBetween(1000, 9999),
            'staff_name' => $this->faker->name(),
            'staff_unit_name' => $this->faker->word().'学院',
            'violation_type' => $this->faker->numberBetween(35, 39),
            'severity_level' => $severity,
            'violation_at' => now()->subDays($this->faker->numberBetween(0, 90)),
            'deduction_points' => ['A' => 5, 'B' => 10, 'C' => 20][$severity],
            'data_source' => 'manual',
            'handler_department' => $this->faker->word().'部门',
            'handler_user_id' => null,
            'deduction_basis' => $this->faker->sentence(),
            'evidence_attachments' => [],
            'verification_status' => 'verified',
            'verified_by' => null,
            'verified_at' => now(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
