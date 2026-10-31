<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Who controls Mecatol Rex: under Absol's Representative Government they cast 2 votes instead of 1
            $table->foreignUuid('mecatol_rex_player_id')->nullable()->after('term_limits')->constrained('players')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mecatol_rex_player_id');
        });
    }
};
