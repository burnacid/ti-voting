<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Thunder's Edge: The Fracture only enters play during the game, the Speaker enables it
            $table->boolean('fracture_in_play')->default(false)->after('milty_draft_data');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('fracture_in_play');
        });
    }
};
