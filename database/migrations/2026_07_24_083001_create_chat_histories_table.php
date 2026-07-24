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
        if (!Schema::hasTable('chat_histories')) {
            Schema::create('chat_histories', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 100)->index('idx_session');
                $table->string('intent', 50)->nullable()->default('LAINNYA');
                $table->text('user_message');
                $table->text('agent_response');
                $table->string('agent_type', 50);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
