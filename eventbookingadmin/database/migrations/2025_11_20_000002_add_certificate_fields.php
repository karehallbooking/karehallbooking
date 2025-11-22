<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'certificate_template_path')) {
                $table->string('certificate_template_path')->nullable();
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('registrations', 'certificate_path')) {
                $table->string('certificate_path')->nullable();
            }
            if (!Schema::hasColumn('registrations', 'certificate_issued')) {
                $table->boolean('certificate_issued')->default(false);
            }
            if (!Schema::hasColumn('registrations', 'certificate_issued_at')) {
                $table->timestamp('certificate_issued_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'certificate_template_path')) {
                $table->dropColumn('certificate_template_path');
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            $columns = ['certificate_path', 'certificate_issued', 'certificate_issued_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('registrations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

