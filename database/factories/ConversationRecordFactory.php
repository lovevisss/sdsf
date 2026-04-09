<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConversationRecord>
 */
class ConversationRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'advisor_id' => User::whereRole('advisor')->firstOrCreate(
                ['email' => 'advisor@example.com'],
                ['name' => 'Advisor', 'password' => bcrypt('password'), 'role' => 'advisor']
            )->id,
            'student_id' => User::whereRole('student')->firstOrCreate(
                ['email' => 'student@example.com'],
                ['name' => 'Student', 'password' => bcrypt('password'), 'role' => 'student']
            )->id,
            'class_model_id' => ClassModel::factory(),
            'conversation_form' => $this->faker->randomElement(['talk', 'consultation', 'sport', 'meal', 'tea_break', 'seminar', 'other']),
            'conversation_method' => $this->faker->randomElement(['one_on_one', 'one_on_many', 'dorm_visit', 'class_meeting', 'family_contact']),
            'conversation_reason' => $this->faker->randomElement(['academic', 'life', 'psychology', 'discipline', 'other']),
            'topic' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
            'conversation_at' => $this->faker->dateTimeBetween('-1 year'),
            'location' => $this->faker->randomElement(['办公室', '学生宿舍', '咖啡厅', '教室']),
        ];
    }
}
