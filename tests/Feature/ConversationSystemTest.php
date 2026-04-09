<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\ConversationAppointment;
use App\Models\ConversationRecord;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ConversationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_advisor_can_view_pending_appointments(): void
    {
        $advisor = User::factory()->create(['role' => 'advisor']);
        $student = User::factory()->create(['role' => 'student']);

        ConversationAppointment::factory()->create([
            'advisor_id' => $advisor->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($advisor)
            ->get('/conversations/appointments?status=pending');

        $response->assertStatus(200);
    }

    public function test_advisor_can_confirm_appointment(): void
    {
        $advisor = User::factory()->create(['role' => 'advisor']);
        $student = User::factory()->create(['role' => 'student']);

        $appointment = ConversationAppointment::factory()->create([
            'advisor_id' => $advisor->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);

        $this->actingAs($advisor)
            ->patch("/conversations/appointments/{$appointment->id}/confirm");

        $this->assertDatabaseHas('conversation_appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_advisor_can_view_own_appointment_detail(): void
    {
        $advisor = User::factory()->create(['role' => 'advisor']);
        $student = User::factory()->create(['role' => 'student']);

        $appointment = ConversationAppointment::factory()->create([
            'advisor_id' => $advisor->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($advisor)
            ->get("/conversations/appointments/{$appointment->id}");

        $response->assertStatus(200);
    }

    public function test_advisor_can_create_conversation_record(): void
    {
        $advisor = User::factory()->create(['role' => 'advisor']);
        $student = User::factory()->create(['role' => 'student']);
        $department = Department::factory()->create();
        $class = ClassModel::factory()->create(['department_id' => $department->id]);

        $this->actingAs($advisor)
            ->post('/conversations/records', [
                'student_id' => $student->id,
                'class_model_id' => $class->id,
                'conversation_form' => 'talk',
                'conversation_method' => 'one_on_one',
                'conversation_reason' => 'academic',
                'topic' => 'Test Topic',
                'content' => 'Test content',
                'conversation_at' => now(),
                'location' => 'Office',
            ]);

        $this->assertDatabaseHas('conversation_records', [
            'advisor_id' => $advisor->id,
            'student_id' => $student->id,
            'topic' => 'Test Topic',
        ]);
    }

    public function test_student_can_initiate_appointment(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $advisor = User::factory()->create(['role' => 'advisor']);

        $this->actingAs($student)
            ->post('/conversations/appointments', [
                'advisor_id' => $advisor->id,
                'appointment_type' => 'talk',
                'remarks' => 'Test remarks',
            ]);

        $this->assertDatabaseHas('conversation_appointments', [
            'student_id' => $student->id,
            'advisor_id' => $advisor->id,
            'status' => 'pending',
        ]);
    }

    public function test_advisor_can_view_conversation_dashboard(): void
    {
        $advisor = User::factory()->create(['role' => 'advisor']);

        ConversationRecord::factory(5)->create([
            'advisor_id' => $advisor->id,
        ]);

        $response = $this->actingAs($advisor)
            ->get('/conversations/dashboard');

        $response->assertStatus(200);
    }

    public function test_advisor_can_view_conversation_records(): void
    {
        $advisor = User::factory()->create(['role' => 'advisor']);

        ConversationRecord::factory(3)->create([
            'advisor_id' => $advisor->id,
        ]);

        $response = $this->actingAs($advisor)
            ->get('/conversations/records');

        $response->assertStatus(200);
    }

    public function test_advisor_can_update_own_record(): void
    {
        $advisor = User::factory()->create(['role' => 'advisor']);
        $student = User::factory()->create(['role' => 'student']);

        $record = ConversationRecord::factory()->create([
            'advisor_id' => $advisor->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($advisor)
            ->patch("/conversations/records/{$record->id}", [
                'conversation_form' => 'consultation',
                'conversation_method' => 'one_on_one',
                'conversation_reason' => 'life',
                'topic' => 'Updated Topic',
                'conversation_at' => now(),
            ]);

        $this->assertDatabaseHas('conversation_records', [
            'id' => $record->id,
            'topic' => 'Updated Topic',
        ]);
    }

    public function test_student_cannot_create_conversation_record(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $department = Department::factory()->create();
        $class = ClassModel::factory()->create(['department_id' => $department->id]);

        $response = $this->actingAs($student)
            ->post('/conversations/records', [
                'student_id' => $student->id,
                'class_model_id' => $class->id,
                'conversation_form' => 'talk',
                'conversation_method' => 'one_on_one',
                'conversation_reason' => 'academic',
                'topic' => 'Test Topic',
                'conversation_at' => now(),
            ]);

        $response->assertStatus(403);
    }
}
