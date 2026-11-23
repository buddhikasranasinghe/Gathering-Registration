<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->restrictOnDelete();
            $table->string('ticket_number')->unique();
            $table->timestamp('issued_at');
            $table->string('status');
            $table->string('file_path')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('participant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_tickets');
    }
};
