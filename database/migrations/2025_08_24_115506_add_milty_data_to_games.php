<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('milty_url')->after('status')->nullable();
            $table->string('milty_draft_id')->after('milty_url')->nullable();
            $table->json('milty_draft_data')->after('milty_draft_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('milty_url');
            $table->dropColumn('milty_draft_id');
            $table->dropColumn('milty_draft_data');
        });
    }
};
