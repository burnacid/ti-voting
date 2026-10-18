<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // The law Representative Government is in effect: each player casts 1 vote instead of spending influence
            $table->boolean('representative_government')->default(false)->after('fracture_in_play');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('representative_government');
        });
    }
};
