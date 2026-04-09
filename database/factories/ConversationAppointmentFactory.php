<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConversationAppointment>
 */
class ConversationAppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::whereRole('student')->firstOrCreate(
                ['email' => 'student@example.com'],
                ['name' => 'Student', 'password' => bcrypt('password'), 'role' => 'student']
            )->id,
            'advisor_id' => User::whereRole('advisor')->firstOrCreate(
                ['email' => 'advisor@example.com'],
                ['name' => 'Advisor', 'password' => bcrypt('password'), 'role' => 'advisor']
            )->id,
            'appointment_type' => $this->faker->randomElement(['talk', 'consultation', 'other']),
            'remarks' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'appointed_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }
}
