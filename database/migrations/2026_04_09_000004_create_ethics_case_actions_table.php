<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ethics_case_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ethics_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action_type', ['accept', 'assign', 'investigate', 'rectify', 'feedback', 'close', 'reject', 'note']);
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['ethics_case_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethics_case_actions');
    }
};

