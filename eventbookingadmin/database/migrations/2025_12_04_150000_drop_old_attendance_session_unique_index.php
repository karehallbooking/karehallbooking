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
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // Drop the old unique index on (event_id, session_date)
            // that was created in 2025_12_03_115131_create_attendance_sessions_table.php
            // The new composite index (event_id, session_date, session_number)
            // defined in 2025_12_03_120318_add_session_number_to_attendance_sessions_table.php
            // is the one we want to keep so multiple sessions per day are allowed.
            $table->dropUnique('event_session_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // Recreate the old unique index if this migration is rolled back
            $table->unique(['event_id', 'session_date'], 'event_session_date_unique');
        });
    }
};


