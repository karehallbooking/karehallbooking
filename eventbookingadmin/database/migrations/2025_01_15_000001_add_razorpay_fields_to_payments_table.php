<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->default('razorpay')->after('registration_id');
            $table->string('razorpay_order_id')->unique()->nullable()->after('gateway');
            $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id');
            $table->string('razorpay_signature')->nullable()->after('razorpay_payment_id');
            $table->string('currency')->default('INR')->after('amount');
            $table->json('meta')->nullable()->after('notes');
            // Note: For SQL Server, we keep the existing status enum as-is
            // The application will handle 'success' and 'failed' values even if the enum doesn't include them
            // This is a limitation of SQL Server enum handling in Laravel
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'razorpay_order_id',
                'razorpay_payment_id',
                'razorpay_signature',
                'currency',
                'meta',
            ]);
            // Note: Status column remains unchanged for SQL Server compatibility
        });
    }
};

