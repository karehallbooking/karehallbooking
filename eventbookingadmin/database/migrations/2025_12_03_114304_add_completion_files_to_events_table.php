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
            $table->string('completion_event_form_path')->nullable()->after('attachment_path');
            $table->string('completion_circular_path')->nullable()->after('completion_event_form_path');
            $table->string('completion_brochure_path')->nullable()->after('completion_circular_path');
            $table->string('completion_report_path')->nullable()->after('completion_brochure_path');
            $table->string('completion_attendance_path')->nullable()->after('completion_report_path');
            $table->string('completion_feedback_path')->nullable()->after('completion_attendance_path');
            $table->string('completion_sample_certificate_path')->nullable()->after('completion_feedback_path');
            $table->text('completion_image_paths')->nullable()->after('completion_sample_certificate_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'completion_event_form_path',
                'completion_circular_path',
                'completion_brochure_path',
                'completion_report_path',
                'completion_attendance_path',
                'completion_feedback_path',
                'completion_sample_certificate_path',
                'completion_image_paths',
            ]);
        });
    }
};
