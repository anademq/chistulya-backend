<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pet_item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('title', 64);
            $table->unsignedSmallInteger('order_column')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('pet_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('category_id')->constrained('pet_item_categories')->restrictOnDelete();
            $table->string('title', 150);
            $table->string('short_description', 250)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_available')->default(false);
            $table->json('requirements')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('child_pet_items', function (Blueprint $table) {
            $table->foreignUuid('pet_item_id')->constrained('pet_items')->cascadeOnDelete();
            $table->foreignUuid('child_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_equipped')->default(false);
            $table->timestamp('purchased_at')->useCurrent();
            $table->primary(['pet_item_id', 'child_id']);
            $table->index(['child_id', 'is_equipped']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_pet_items');
        Schema::dropIfExists('pet_items');
        Schema::dropIfExists('pet_item_categories');
    }
};
