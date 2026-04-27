<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ethics_professional_violations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ethics_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('violator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorder_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('staff_no');
            $table->string('staff_name');
            $table->string('staff_unit_name')->nullable();
            $table->unsignedTinyInteger('violation_type');
            $table->timestamp('violation_at');
            $table->decimal('deduction_points', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['violation_type', 'violation_at'], 'epv_type_at_idx');
            $table->index(['violator_user_id', 'violation_at'], 'epv_user_at_idx');
            $table->index(['staff_no', 'violation_at'], 'epv_staff_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethics_professional_violations');
    }
};
