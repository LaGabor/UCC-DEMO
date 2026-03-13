<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('sender_type', 32);
            $table->foreignId('sender_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('message_type', 32);
            $table->string('language', 5)->nullable();;
            $table->text('message_text')->nullable();
            $table->string('answer_code')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_user_id', 'created_at']);
            $table->index(['sender_type', 'created_at']);
            $table->fullText('message_text');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};
