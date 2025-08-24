<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['speaker_id']);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('speaker_id')->references('id')->on('players')->onDelete('set null');
        });
    }
};
