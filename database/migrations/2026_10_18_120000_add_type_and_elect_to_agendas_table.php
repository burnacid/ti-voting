<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->string('type')->nullable()->after('description'); // law or directive, null for custom agendas
            $table->string('elect')->nullable()->after('type'); // what is elected (player, planet, ...), null for For/Against
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['type', 'elect']);
        });
    }
};
