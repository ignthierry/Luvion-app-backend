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
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 100);
                $table->string('customer_name', 100)->nullable();
                $table->dateTime('appointment_date');
                $table->string('agenda', 255);
                $table->enum('status', ['PENDING_CONFIRMATION', 'CONFIRMED', 'CANCELLED'])->default('PENDING_CONFIRMATION');
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
