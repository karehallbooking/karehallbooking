<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('organizer');
            $table->string('department');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('venue');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->integer('capacity');
            $table->boolean('is_paid')->default(false);
            $table->decimal('amount', 10, 2)->nullable();
            $table->unsignedInteger('registrations_count')->default(0);
            $table->string('brochure_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

