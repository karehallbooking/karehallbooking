<?php

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
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_logs', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable()->after('event_id');
            }
            if (!Schema::hasColumn('attendance_logs', 'attendance_date')) {
                $table->date('attendance_date')->nullable()->after('session_number');
            }
            // For SQL Server, avoid cascading FK here to prevent multiple cascade paths issues.
            // You can add a foreign key manually at DB level if needed.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'session_id')) {
                $table->dropColumn('session_id');
            }
            if (Schema::hasColumn('attendance_logs', 'attendance_date')) {
                $table->dropColumn('attendance_date');
            }
        });
    }
};
