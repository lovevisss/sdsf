<?php

namespace Database\Factories;

use App\Models\EthicsAssessment;
use App\Models\EthicsProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthicsAssessment>
 */
class EthicsAssessmentFactory extends Factory
{
    protected $model = EthicsAssessment::class;

    public function definition(): array
    {
        $scores = [
            'political_literacy_score' => $this->faker->randomFloat(2, 70, 100),
            'education_score' => $this->faker->randomFloat(2, 70, 100),
            'academic_integrity_score' => $this->faker->randomFloat(2, 70, 100),
            'role_model_score' => $this->faker->randomFloat(2, 70, 100),
            'integrity_score' => $this->faker->randomFloat(2, 70, 100),
            'service_score' => $this->faker->randomFloat(2, 70, 100),
        ];

        $totalScore = array_sum($scores) / count($scores);

        return [
            'ethics_profile_id' => EthicsProfile::factory(),
            'year' => (int) now()->format('Y'),
            'evaluator_id' => User::factory(),
            'evaluator_role' => $this->faker->randomElement(['self', 'department', 'peer', 'student', 'supervisor']),
            ...$scores,
            'total_score' => round($totalScore, 2),
            'grade' => 'qualified',
            'comment' => $this->faker->sentence(),
            'assessed_at' => now(),
        ];
    }
}

