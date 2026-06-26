<?php

use App\Enums\ProfileRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('name', 128);
            $table->boolean('sex')->nullable();
            $table->enum('role', ProfileRole::cases())->index();
            $table->date('date_of_birth')->nullable();
            $table->string('city', 128)->nullable();
            $table->string('timezone', 64)->default('Europe/Moscow');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
