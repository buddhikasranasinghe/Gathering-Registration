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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('call_name')->nullable();
            $table->string('batch')->nullable();
            $table->unsignedTinyInteger('batch_number')->nullable();
            $table->string('phone', 20)->nullable()->index();
            $table->string('nic', 20)->nullable()->index();
            $table->boolean('spouse_attending')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('sheet_key')->nullable()->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->text('archive_note')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
