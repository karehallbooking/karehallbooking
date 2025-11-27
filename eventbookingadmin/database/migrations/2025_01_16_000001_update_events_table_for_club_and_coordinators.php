<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add new fields
            $table->string('event_club')->nullable()->after('organizer');
            $table->string('event_club_other')->nullable()->after('event_club');
            $table->string('faculty_coordinator_name')->nullable()->after('venue');
            $table->string('faculty_coordinator_contact')->nullable()->after('faculty_coordinator_name');
            $table->string('student_coordinator_name')->nullable()->after('faculty_coordinator_contact');
            $table->string('student_coordinator_contact')->nullable()->after('student_coordinator_name');
            
            // Remove department column (we'll keep it for now but make it nullable for backward compatibility)
            // Actually, let's keep it nullable instead of dropping to avoid data loss
            $table->string('department')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_club',
                'event_club_other',
                'faculty_coordinator_name',
                'faculty_coordinator_contact',
                'student_coordinator_name',
                'student_coordinator_contact',
            ]);
            $table->string('department')->nullable(false)->change();
        });
    }
};

