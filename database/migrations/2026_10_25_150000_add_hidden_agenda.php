<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Hidden Agenda: votes are secret and only the totals are revealed. Off, every player sees how each player voted
            $table->boolean('hidden_agenda')->default(true)->after('show_results');
        });

        Schema::table('agendas', function (Blueprint $table) {
            // Copied from the game when the agenda is revealed, so changing the setting later never reveals its votes
            $table->boolean('secret_votes')->default(true)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('hidden_agenda');
        });

        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('secret_votes');
        });
    }
};
