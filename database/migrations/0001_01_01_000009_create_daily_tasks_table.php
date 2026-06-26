<?php

use App\Enums\DailyTaskScope;
use App\Enums\DailyTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_task_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('title', 64);
            $table->unsignedSmallInteger('order_column')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('scope', DailyTaskScope::cases())->index();
            $table->foreignId('category_id')->constrained('daily_task_categories')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('short_description', 250)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('daily_task_assignments', function (Blueprint $table) {
            $table->foreignUuid('daily_task_id')->constrained('daily_tasks')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['daily_task_id', 'child_id']);
        });

        Schema::create('child_daily_tasks', function (Blueprint $table) {
            $table->foreignUuid('daily_task_id')->constrained('daily_tasks')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', DailyTaskStatus::cases())->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->timestamps();
            $table->primary(['daily_task_id', 'child_id']);
            $table->index(['child_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_daily_tasks');
        Schema::dropIfExists('daily_task_assignments');
        Schema::dropIfExists('daily_tasks');
        Schema::dropIfExists('daily_task_categories');
    }
};
