<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // The Speaker plays with the agenda deck of Absol's Lab instead of the official agendas
            $table->boolean('absol_agendas')->default(false)->after('representative_government');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('absol_agendas');
        });
    }
};
