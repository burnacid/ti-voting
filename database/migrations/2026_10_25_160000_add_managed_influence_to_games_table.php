<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Managed influence: players enter their influence when the Speaker starts the agenda phase, votes subtract from it
            $table->boolean('managed_influence')->default(false)->after('hidden_agenda');
            $table->timestamp('agenda_phase_started_at')->nullable()->after('managed_influence');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['managed_influence', 'agenda_phase_started_at']);
        });
    }
};
