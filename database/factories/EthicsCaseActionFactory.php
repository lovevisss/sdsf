<?php

namespace Database\Factories;

use App\Models\EthicsCase;
use App\Models\EthicsCaseAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthicsCaseAction>
 */
class EthicsCaseActionFactory extends Factory
{
    protected $model = EthicsCaseAction::class;

    public function definition(): array
    {
        return [
            'ethics_case_id' => EthicsCase::factory(),
            'actor_id' => User::factory(),
            'action_type' => $this->faker->randomElement(['accept', 'assign', 'investigate', 'rectify', 'feedback', 'close', 'reject', 'note']),
            'notes' => $this->faker->sentence(),
            'attachments' => [],
            'happened_at' => now(),
        ];
    }
}

