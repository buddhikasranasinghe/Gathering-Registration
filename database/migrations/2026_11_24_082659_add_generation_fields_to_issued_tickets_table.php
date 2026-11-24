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
        Schema::table('issued_tickets', function (Blueprint $table): void {
            $table->string('file_disk')->nullable()->after('file_path');
            $table->decimal('ticket_price', 10, 2)->nullable()->after('file_disk');
            $table->json('attendees')->nullable()->after('ticket_price');
            $table->string('issued_by_name')->nullable()->after('issued_by');
            $table->timestamp('deactivated_at')->nullable()->after('status');
            $table->foreignId('deactivated_by')
                ->nullable()
                ->after('deactivated_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('deactivated_by_name')->nullable()->after('deactivated_by');
            $table->timestamp('emailed_at')->nullable()->after('deactivated_by_name');
            $table->string('emailed_to')->nullable()->after('emailed_at');
            $table->unsignedInteger('email_send_count')->default(0)->after('emailed_to');
        });

        DB::table('issued_tickets')->where('status', 'issued')->update(['status' => 'active']);
        DB::table('issued_tickets')->where('status', 'cancelled')->update(['status' => 'deactivated']);
    }

    public function down(): void
    {
        DB::table('issued_tickets')->where('status', 'active')->update(['status' => 'issued']);
        DB::table('issued_tickets')->where('status', 'deactivated')->update(['status' => 'cancelled']);

        Schema::table('issued_tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'file_disk',
                'ticket_price',
                'attendees',
                'issued_by_name',
                'deactivated_at',
                'deactivated_by',
                'deactivated_by_name',
                'emailed_at',
                'emailed_to',
                'email_send_count',
            ]);
        });
    }
};
