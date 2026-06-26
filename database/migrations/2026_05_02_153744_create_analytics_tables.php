<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_task_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('daily_task_categories')->nullOnDelete();
            $table->date('date');
            $table->unsignedInteger('selected_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);
            $table->timestamps();
            $table->unique(['child_id', 'category_id', 'date']);
            $table->index(['child_id', 'date']);
        });

        Schema::create('challenge_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('challenge_categories')->nullOnDelete();
            $table->date('date');
            $table->unsignedInteger('selected_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->unique(['child_id', 'category_id', 'date']);
            $table->index(['child_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_analytics');
        Schema::dropIfExists('daily_task_analytics');
    }
};
