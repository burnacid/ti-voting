<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agenda_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('player_id')->constrained()->onDelete('cascade');
            $table->string('option'); // The selected option
            $table->integer('influence_spent')->default(0); // TI4 influence tokens
            $table->timestamps();

            $table->unique(['agenda_id', 'player_id']); // One vote per player per agenda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
