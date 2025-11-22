<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'organizer')) {
                $table->string('organizer')->nullable();
            }
            if (!Schema::hasColumn('events', 'department')) {
                $table->string('department')->nullable();
            }
            if (!Schema::hasColumn('events', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (!Schema::hasColumn('events', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            if (!Schema::hasColumn('events', 'start_time')) {
                $table->time('start_time')->nullable();
            }
            if (!Schema::hasColumn('events', 'end_time')) {
                $table->time('end_time')->nullable();
            }
            if (!Schema::hasColumn('events', 'is_paid')) {
                $table->boolean('is_paid')->default(false);
            }
            if (!Schema::hasColumn('events', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('events', 'registrations_count')) {
                $table->unsignedInteger('registrations_count')->default(0);
            }
            if (!Schema::hasColumn('events', 'brochure_path')) {
                $table->string('brochure_path')->nullable();
            }
            if (!Schema::hasColumn('events', 'attachment_path')) {
                $table->string('attachment_path')->nullable();
            }
        });

        if (Schema::hasColumn('events', 'event_date')) {
            DB::statement('UPDATE events SET start_date = COALESCE(start_date, event_date)');
            DB::statement('UPDATE events SET end_date = COALESCE(end_date, event_date)');
        }

        if (Schema::hasColumn('events', 'event_time')) {
            DB::statement('UPDATE events SET start_time = COALESCE(start_time, event_time)');
            DB::statement('UPDATE events SET end_time = COALESCE(end_time, event_time)');
        }

        if (Schema::hasColumn('events', 'is_free')) {
            DB::statement('UPDATE events SET is_paid = CASE WHEN is_free = 1 THEN 0 ELSE 1 END WHERE is_free IS NOT NULL');
        }

        if (Schema::hasColumn('events', 'fee')) {
            DB::statement('UPDATE events SET amount = COALESCE(amount, fee)');
        }

        DB::table('events')->whereNull('organizer')->update(['organizer' => 'Unknown']);
        DB::table('events')->whereNull('department')->update(['department' => 'General']);
    }

    public function down(): void
    {
        $drops = [
            'organizer',
            'department',
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'is_paid',
            'amount',
            'registrations_count',
            'brochure_path',
            'attachment_path',
        ];

        foreach ($drops as $column) {
            if (Schema::hasColumn('events', $column)) {
                Schema::table('events', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

