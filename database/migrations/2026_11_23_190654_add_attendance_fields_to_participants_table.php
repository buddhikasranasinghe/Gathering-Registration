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
            $table->boolean('attending')->default(true)->after('nic');
            $table->boolean('attendance_manually_set')->default(false)->after('attending');
        });

        DB::table('participants')
            ->whereNull('spouse_attending')
            ->update(['spouse_attending' => false]);
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['attending', 'attendance_manually_set']);
        });
    }
};
