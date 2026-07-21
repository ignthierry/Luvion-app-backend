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
            $table->string('payment_url')->nullable()->after('payment_status');
            $table->string('snap_token')->nullable()->after('payment_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_url', 'snap_token']);
        });
    }
};
