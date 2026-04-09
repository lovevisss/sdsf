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
        if (! Schema::hasTable('conversation_records')) {
            Schema::create('conversation_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('advisor_id')->constrained('users')->comment('Advisor/Teacher conducting the conversation');
                $table->foreignId('student_id')->constrained('users')->comment('Student being spoken with');
                $table->foreignId('class_model_id')->constrained();
                $table->enum('conversation_form', ['talk', 'consultation', 'sport', 'meal', 'tea_break', 'seminar', 'other'])->comment('Form of conversation');
                $table->enum('conversation_method', ['one_on_one', 'one_on_many', 'dorm_visit', 'class_meeting', 'family_contact'])->comment('Method of conversation');
                $table->enum('conversation_reason', ['academic', 'life', 'psychology', 'discipline', 'other'])->comment('Primary reason for conversation');
                $table->string('topic')->comment('Main topic discussed');
                $table->text('content')->nullable()->comment('Detailed conversation notes');
                $table->datetime('conversation_at')->comment('When the conversation took place');
                $table->string('location')->nullable()->comment('Where the conversation took place');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_records');
    }
};
