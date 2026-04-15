<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ethics_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ethics_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('channel', ['pc', 'mobile', 'wechat', 'wecom', 'other']);
            $table->boolean('is_anonymous')->default(false);
            $table->string('title');
            $table->text('content');
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->enum('status', ['reported', 'accepted', 'assigned', 'investigating', 'resolved', 'closed', 'rejected'])->default('reported');
            $table->timestamp('reported_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'risk_level']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethics_cases');
    }
};

