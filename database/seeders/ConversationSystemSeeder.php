<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\ConversationAppointment;
use App\Models\ConversationRecord;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConversationSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create departments
        $departments = Department::factory(3)->create();

        // Create advisor users
        $advisors = User::factory(5)->create([
            'role' => 'advisor',
            'department_id' => $departments->random(),
        ]);

        // Create student users
        $students = User::factory(20)->create([
            'role' => 'student',
            'department_id' => $departments->random(),
        ]);

        // Create classes
        $classes = ClassModel::factory(6)
            ->for($departments->random(), 'department')
            ->create();

        // Create conversation records
        ConversationRecord::factory(50)
            ->create([
                'advisor_id' => $advisors->random(),
                'student_id' => $students->random(),
                'class_model_id' => $classes->random(),
            ]);

        // Create conversation appointments
        ConversationAppointment::factory(15)
            ->create([
                'student_id' => $students->random(),
                'advisor_id' => $advisors->random(),
            ]);
    }
}
