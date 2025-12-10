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
            $table->unsignedInteger('session_number')->default(1)->after('session_date');
        });

        // Optional: add composite unique index including session_number
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->unique(['event_id', 'session_date', 'session_number'], 'event_session_date_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropUnique('event_session_date_number_unique');
            $table->dropColumn('session_number');
        });
    }
};
