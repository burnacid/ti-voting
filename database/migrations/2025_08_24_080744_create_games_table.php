<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 6)->unique(); // 6-character game code
            $table->string('name');
            $table->foreignUuid('speaker_id')->nullable();
            $table->enum('status', ['waiting', 'active', 'completed'])->default('waiting');
            $table->timestamps();

            $table->foreign('speaker_id')->references('id')->on('players')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
