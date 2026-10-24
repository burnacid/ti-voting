<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->text('message');
            // Optional name or contact details left by the sender
            $table->string('name', 100)->nullable();
            // Code of the game it was sent from, kept after the game is deleted
            $table->string('game', 20)->nullable()->index();
            $table->string('url')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
