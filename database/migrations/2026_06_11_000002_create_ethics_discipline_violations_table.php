<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ethics_discipline_violations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ethics_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('violator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorder_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('staff_no');
            $table->string('staff_name');
            $table->string('staff_unit_name')->nullable();
            $table->unsignedTinyInteger('violation_type');
            $table->string('severity_level', 1)->nullable();
            $table->timestamp('violation_at');
            $table->decimal('deduction_points', 5, 2);
            $table->string('data_source')->default('manual');
            $table->string('handler_department')->nullable();
            $table->foreignId('handler_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deduction_basis')->nullable();
            $table->json('evidence_attachments')->nullable();
            $table->string('verification_status')->default('verified');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['violation_type', 'violation_at'], 'edv_type_at_idx');
            $table->index(['violator_user_id', 'violation_at'], 'edv_user_at_idx');
            $table->index(['staff_no', 'violation_at'], 'edv_staff_at_idx');
            $table->index(['severity_level', 'verification_status'], 'edv_severity_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethics_discipline_violations');
    }
};
