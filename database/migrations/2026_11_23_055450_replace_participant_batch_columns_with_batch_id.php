<?php

declare(strict_types=1);

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
        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('batch_id')
                ->nullable()
                ->after('call_name')
                ->constrained()
                ->restrictOnDelete();
            $table->index('batch_id');

            $table->dropIndex('participants_batch_number_index');
            $table->dropColumn(['batch', 'batch_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('batch')->nullable();
            $table->unsignedTinyInteger('batch_number')->nullable()->index();
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
