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
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'faculty_token')) {
                // Token provided by the college portal to identify faculty bookings
                $table->string('faculty_token')->nullable()->after('organizer_designation');
                $table->index('faculty_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'faculty_token')) {
                $table->dropIndex(['faculty_token']);
                $table->dropColumn('faculty_token');
            }
        });
    }
};
