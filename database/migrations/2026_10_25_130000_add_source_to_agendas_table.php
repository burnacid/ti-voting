<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Deck of the preset agenda, e.g. pok or absol, shown as a tag on the card. Null for the base game and custom agendas
            $table->string('source')->nullable()->after('elect');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
