<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agenda_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('player_id')->constrained()->onDelete('cascade');
            $table->string('source'); // The card or ability that predicts, e.g. leadership_rider
            $table->string('option'); // The predicted outcome
            $table->timestamps();

            $table->unique(['agenda_id', 'player_id', 'source']); // Each card or ability once per player per agenda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
