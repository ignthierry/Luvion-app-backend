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
        Schema::table('client_orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status'); // unpaid, paid, overdue, failed
            $table->integer('billing_due_day')->nullable()->after('payment_status'); // e.g. 15 for 15th of the month
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'billing_due_day']);
        });
    }
};
