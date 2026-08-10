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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference')->unique();
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};