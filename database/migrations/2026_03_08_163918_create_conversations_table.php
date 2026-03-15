<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('assigned_agent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 32)->default('open');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_assign_request')->nullable();
            $table->timestamp('last_assigned_at')->nullable();
            $table->timestamp('last_closed_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'last_message_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
