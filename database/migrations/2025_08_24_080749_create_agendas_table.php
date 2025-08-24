<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('game_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->json('options'); // Store voting options as JSON
            $table->enum('status', ['pending', 'voting', 'completed'])->default('pending');
            $table->timestamp('voting_started_at')->nullable();
            $table->timestamp('voting_ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
