<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_rewards', function (Blueprint $table) {
            $table->unsignedSmallInteger('day')->primary();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->timestamps();
        });

        Schema::create('child_daily_rewards', function (Blueprint $table) {
            $table->foreignUuid('child_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('current_day')->default(1)->index();
            $table->timestamp('last_claimed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_daily_rewards');
        Schema::dropIfExists('daily_rewards');
    }
};
