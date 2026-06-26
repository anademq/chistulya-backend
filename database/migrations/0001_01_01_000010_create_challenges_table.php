<?php

use App\Enums\ChallengeScope;
use App\Enums\ChallengeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('challenge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('title', 64);
            $table->unsignedSmallInteger('order_column')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('scope', ChallengeScope::cases())->index();
            $table->foreignId('category_id')->constrained('challenge_categories')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('short_description', 250)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('challenge_assignments', function (Blueprint $table) {
            $table->foreignUuid('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['challenge_id', 'child_id']);
        });

        Schema::create('child_challenges', function (Blueprint $table) {
            $table->foreignUuid('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ChallengeStatus::cases())->index();
            $table->unsignedSmallInteger('progress_days')->default(0);
            $table->timestamp('last_progress_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->timestamps();
            $table->primary(['challenge_id', 'child_id']);
            $table->index(['child_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_challenges');
        Schema::dropIfExists('challenge_assignments');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('challenge_categories');
    }
};
