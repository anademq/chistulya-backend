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
        Schema::create('link_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('user_links', function (Blueprint $table) {
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('linked_at')->useCurrent();
            $table->primary(['child_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_tokens');
        Schema::dropIfExists('user_links');
    }
};
