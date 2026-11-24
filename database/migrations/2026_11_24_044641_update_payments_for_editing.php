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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('recorded_by_name')->nullable()->after('recorded_by');
            $table->foreignId('updated_by')
                ->nullable()
                ->after('recorded_by_name')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('updated_by_name')->nullable()->after('updated_by');
        });

        $this->backfillRecordedByNames();

        Schema::table('payments', function (Blueprint $table) {
            $table->date('paid_at')->change();
            $table->dropColumn('note');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Note values removed by up() cannot be restored.
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->change();
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['updated_by_name', 'recorded_by_name']);
        });
    }

    private function backfillRecordedByNames(): void
    {
        $userNames = DB::table('users')->pluck('name', 'id');

        DB::table('payments')
            ->whereNotNull('recorded_by')
            ->chunkById(100, function ($payments) use ($userNames): void {
                foreach ($payments as $payment) {
                    $name = $userNames->get($payment->recorded_by);

                    if ($name !== null) {
                        DB::table('payments')
                            ->where('id', $payment->id)
                            ->update(['recorded_by_name' => $name]);
                    }
                }
            });
    }
};
