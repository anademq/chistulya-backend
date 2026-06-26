<?php

use App\Enums\AchievementStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 150);
            $table->string('short_description', 250)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_available')->default(false);
            $table->json('requirements')->nullable();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('child_achievements', function (Blueprint $table) {
            $table->foreignUuid('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', AchievementStatus::cases());
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->primary(['achievement_id', 'child_id']);
            $table->index(['child_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_achievements');
        Schema::dropIfExists('achievements');
    }
};
