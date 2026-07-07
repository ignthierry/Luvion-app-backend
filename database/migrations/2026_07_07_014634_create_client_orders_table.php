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
        Schema::create('client_orders', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('company_name');
            $table->string('email');
            $table->string('phone');
            $table->string('website')->nullable();
            
            $table->string('plan_name');
            $table->string('billing_cycle');
            $table->integer('users_count');
            
            $table->text('purpose')->nullable();
            $table->json('addons')->nullable();
            $table->text('integration_needs')->nullable();
            
            $table->string('subdomain')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('theme_color')->nullable();
            
            $table->text('notes')->nullable();
            $table->date('timeline')->nullable();
            
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_orders');
    }
};
