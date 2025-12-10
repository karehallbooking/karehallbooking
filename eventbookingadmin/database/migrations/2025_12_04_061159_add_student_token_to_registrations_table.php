<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds student_token column to registrations table for token-based student separation.
     * This allows each student to see only their own registrations when logged in via the college portal.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Add student_token column - nullable for backward compatibility with existing data
            $table->string('student_token')->nullable()->after('student_id');
            
            // Add index for faster queries when filtering by token
            $table->index('student_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['student_token']);
            $table->dropColumn('student_token');
        });
    }
};
