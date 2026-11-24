<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->restrictOnDelete();
            $table->boolean('participant_from');
            $table->boolean('participant_to');
            $table->boolean('spouse_from');
            $table->boolean('spouse_to');
            $table->string('spouse_name_from')->nullable();
            $table->string('spouse_name_to')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('participant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_changes');
    }
};
