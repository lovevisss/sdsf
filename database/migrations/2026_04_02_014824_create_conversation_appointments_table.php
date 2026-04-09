<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('conversation_appointments')) {
            Schema::create('conversation_appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users');
                $table->foreignId('advisor_id')->constrained('users');
                $table->enum('appointment_type', ['talk', 'consultation', 'other'])->comment('Type of appointment requested');
                $table->text('remarks')->nullable()->comment('Student remarks for appointment');
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->datetime('appointed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_appointments');
    }
};
