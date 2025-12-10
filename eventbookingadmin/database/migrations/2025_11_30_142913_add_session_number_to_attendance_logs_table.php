<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->unsignedInteger('session_number')->default(1)->after('event_id');
        });

        // Note: We rely on application-level checks in QRScannerController to prevent duplicate scans
        // SQL Server doesn't support partial unique indexes easily, so we handle duplicates in code
        // The controller checks for existing non-revoked logs before creating new ones
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropColumn('session_number');
        });
    }
};
