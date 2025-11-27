<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'certificate_text_prefix')) {
                $table->text('certificate_text_prefix')->nullable()->comment('Text before student name (e.g., "This is to certify that Mr/Ms")');
            }
            if (!Schema::hasColumn('events', 'certificate_text_before_date')) {
                $table->text('certificate_text_before_date')->nullable()->comment('Text before date (e.g., "has participated in")');
            }
            if (!Schema::hasColumn('events', 'certificate_text_after_date')) {
                $table->text('certificate_text_after_date')->nullable()->comment('Text after date (e.g., "organized by KARE")');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $columns = ['certificate_text_prefix', 'certificate_text_before_date', 'certificate_text_after_date'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

