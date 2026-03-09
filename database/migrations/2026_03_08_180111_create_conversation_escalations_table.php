<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('requested_by_message_id')
                ->nullable()
                ->constrained('conversation_messages')
                ->nullOnDelete();

            $table->timestamp('requested_at');
            $table->foreignId('assigned_agent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('first_agent_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'requested_at']);
            $table->index(['assigned_agent_id', 'requested_at']);
            $table->index('requested_at');
            $table->index('first_agent_response_at');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_escalations');
    }
};
