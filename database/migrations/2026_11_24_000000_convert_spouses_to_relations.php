<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('primary_participant_id')
                ->nullable()
                ->after('attendance_manually_set')
                ->constrained('participants')
                ->cascadeOnDelete();
            $table->string('relation')->nullable()->after('primary_participant_id');
        });

        $this->migrateSpousesToRelations();

        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['spouse_attending', 'spouse_name']);
        });

        Schema::table('attendance_changes', function (Blueprint $table) {
            $table->dropColumn([
                'spouse_from',
                'spouse_to',
                'spouse_name_from',
                'spouse_name_to',
                'reason',
            ]);
        });

        Schema::table('attendance_changes', function (Blueprint $table) {
            $table->foreignId('relation_id')
                ->nullable()
                ->after('participant_to')
                ->constrained('participants')
                ->nullOnDelete();
            $table->string('relation_type')->nullable()->after('relation_id');
            $table->string('relation_name_from')->nullable()->after('relation_type');
            $table->string('relation_name_to')->nullable()->after('relation_name_from');
            $table->boolean('relation_from')->nullable()->after('relation_name_to');
            $table->boolean('relation_to')->nullable()->after('relation_from');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_changes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('relation_id');
            $table->dropColumn([
                'relation_type',
                'relation_name_from',
                'relation_name_to',
                'relation_from',
                'relation_to',
            ]);
        });

        Schema::table('attendance_changes', function (Blueprint $table) {
            $table->boolean('spouse_from')->default(false)->after('participant_to');
            $table->boolean('spouse_to')->default(false)->after('spouse_from');
            $table->string('spouse_name_from')->nullable()->after('spouse_to');
            $table->string('spouse_name_to')->nullable()->after('spouse_name_from');
            $table->text('reason')->nullable()->after('changed_by');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->boolean('spouse_attending')->nullable()->after('attendance_manually_set');
            $table->string('spouse_name')->nullable()->after('spouse_attending');
        });

        $this->restoreSpousesFromRelations();

        Schema::table('participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_participant_id');
            $table->dropColumn('relation');
        });
    }

    private function migrateSpousesToRelations(): void
    {
        $now = now();

        DB::table('participants')
            ->whereNotNull('spouse_name')
            ->where('spouse_name', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($participants) use ($now): void {
                $relations = [];

                foreach ($participants as $participant) {
                    $alreadyMigrated = DB::table('participants')
                        ->where('primary_participant_id', $participant->id)
                        ->where('relation', 'spouse')
                        ->where('name', $participant->spouse_name)
                        ->exists();

                    if ($alreadyMigrated) {
                        continue;
                    }

                    $relations[] = [
                        'name' => $participant->spouse_name,
                        'batch_id' => $participant->batch_id,
                        'attending' => (bool) $participant->spouse_attending,
                        'attendance_manually_set' => false,
                        'primary_participant_id' => $participant->id,
                        'relation' => 'spouse',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($relations !== []) {
                    DB::table('participants')->insert($relations);
                }
            });
    }

    private function restoreSpousesFromRelations(): void
    {
        DB::table('participants')
            ->whereNotNull('primary_participant_id')
            ->orderBy('id')
            ->chunkById(100, function ($relations): void {
                foreach ($relations as $relation) {
                    if ($relation->relation !== 'spouse') {
                        continue;
                    }

                    DB::table('participants')
                        ->where('id', $relation->primary_participant_id)
                        ->update([
                            'spouse_name' => $relation->name,
                            'spouse_attending' => (bool) $relation->attending,
                        ]);
                }

                DB::table('participants')
                    ->whereIn('id', $relations->pluck('id'))
                    ->delete();
            });
    }
};
