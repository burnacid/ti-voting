<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Someone who lost their session asks to rejoin as this player, another player confirms it is them
            $table->string('rejoin_request_token', 64)->nullable()->after('session_token');
            $table->timestamp('rejoin_requested_at')->nullable()->after('rejoin_request_token');
            $table->timestamp('rejoin_approved_at')->nullable()->after('rejoin_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['rejoin_request_token', 'rejoin_requested_at', 'rejoin_approved_at']);
        });
    }
};
