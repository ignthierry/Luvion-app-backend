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
        Schema::create('customer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->unsignedBigInteger('client_order_id')->nullable();
            $table->string('request_type')->default('custom_feature'); // addon, upgrade_plan, extra_licenses, custom_feature
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, in_progress
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_requests');
    }
};
