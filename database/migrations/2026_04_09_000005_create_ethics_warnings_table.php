<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ethics_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ethics_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('warning_level', ['yellow', 'orange', 'red']);
            $table->enum('source_type', ['teaching', 'research', 'behavior', 'training', 'manual']);
            $table->text('reason');
            $table->enum('status', ['open', 'rectifying', 'closed'])->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['warning_level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethics_warnings');
    }
};

