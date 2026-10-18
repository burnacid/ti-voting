<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_entries', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->index();
            $table->text('message');
            // Code of the game the entry belongs to, kept after the game is deleted
            $table->string('game', 20)->nullable()->index();
            $table->json('context')->nullable();
            // Class, message and location of a logged exception
            $table->json('exception')->nullable();
            $table->longText('trace')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_entries');
    }
};
