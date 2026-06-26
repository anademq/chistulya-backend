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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 150);
            $table->string('short_description', 250)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_available')->default(false);
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->decimal('price', 11, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('subscriptions');
    }
};
