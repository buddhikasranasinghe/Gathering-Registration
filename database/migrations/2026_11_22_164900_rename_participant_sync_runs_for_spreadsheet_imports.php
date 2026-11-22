<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('participant_sync_runs', 'participant_import_runs');
        Schema::table('participant_import_runs', function (Blueprint $table) {
            $table->string('source_name')->nullable()->after('requested_by');
        });
        Schema::table('participants', function (Blueprint $table) {
            $table->renameColumn('sheet_key', 'import_key');
            $table->renameColumn('last_seen_at', 'last_imported_at');
        });

        DB::table('participant_import_runs')
            ->where('status', 'queued')
            ->update([
                'status' => 'failed',
                'finished_at' => now(),
                'failure_reason' => 'Google Sheet sync was removed.',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->renameColumn('import_key', 'sheet_key');
            $table->renameColumn('last_imported_at', 'last_seen_at');
        });
        Schema::table('participant_import_runs', function (Blueprint $table) {
            $table->dropColumn('source_name');
        });
        Schema::rename('participant_import_runs', 'participant_sync_runs');
    }
};
