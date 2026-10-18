<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // System tiles drawn with Star Chart, in order: tile number, who drew it and when
            $table->json('star_chart_tiles')->nullable()->after('representative_government');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('star_chart_tiles');
        });
    }
};
