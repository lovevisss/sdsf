<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ethics_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ethics_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('evaluator_role', ['self', 'department', 'peer', 'student', 'supervisor']);
            $table->decimal('political_literacy_score', 5, 2);
            $table->decimal('education_score', 5, 2);
            $table->decimal('academic_integrity_score', 5, 2);
            $table->decimal('role_model_score', 5, 2);
            $table->decimal('integrity_score', 5, 2);
            $table->decimal('service_score', 5, 2);
            $table->decimal('total_score', 5, 2);
            $table->enum('grade', ['excellent', 'qualified', 'basic_qualified', 'unqualified']);
            $table->text('comment')->nullable();
            $table->timestamp('assessed_at');
            $table->timestamps();

            $table->index(['ethics_profile_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethics_assessments');
    }
};

