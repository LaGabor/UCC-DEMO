<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('occurs_at');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'occurs_at']);
            $table->fullText(['title', 'description']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
