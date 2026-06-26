<?php

use App\Enums\ReminderRepeatPattern;
use App\Enums\ReminderScope;
use App\Enums\ReminderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('scope', ReminderScope::cases())->index();
            $table->string('title', 150);
            $table->string('short_description', 250)->nullable();
            $table->text('description')->nullable();
            $table->enum('repeating_pattern', ReminderRepeatPattern::cases());
            $table->date('date')->nullable();
            $table->time('time');
            $table->char('repeating_days', 7)->nullable();
            $table->enum('status', ReminderStatus::cases())->default(ReminderStatus::Active)->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reminder_assignments', function (Blueprint $table) {
            $table->foreignUuid('reminder_id')->constrained('reminders')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['reminder_id', 'child_id']);
        });

        Schema::create('child_reminder_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('reminder_id')->constrained('reminders')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('sent_at')->useCurrent()->index();
            $table->timestamp('seen_at')->nullable()->index();
            $table->index(['child_id', 'seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_reminder_notifications');
        Schema::dropIfExists('reminder_assignments');
        Schema::dropIfExists('reminders');
    }
};
