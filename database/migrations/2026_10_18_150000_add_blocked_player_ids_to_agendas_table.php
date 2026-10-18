<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Players the Speaker marked as unable to vote, e.g. by Assassinate Representative
            $table->json('blocked_player_ids')->nullable()->after('reveal_step');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('blocked_player_ids');
        });
    }
};
