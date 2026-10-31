<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Absol's law Term Limits is in effect: the speaker token can move after each agenda
            $table->boolean('term_limits')->default(false)->after('representative_government');
        });

        Schema::table('agendas', function (Blueprint $table) {
            // Who was Speaker when the agenda was revealed, as Term Limits looks at how they voted
            $table->foreignUuid('speaker_id')->nullable()->after('game_id')->constrained('players')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speaker_id');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('term_limits');
        });
    }
};
