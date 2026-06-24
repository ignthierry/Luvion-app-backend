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
        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('subtitle');
            $table->string('price');
            $table->string('original_price')->nullable();
            $table->string('price_suffix')->nullable();
            $table->text('description');
            $table->json('features');
            $table->boolean('popular')->default(false);
            $table->string('highlight_color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};
