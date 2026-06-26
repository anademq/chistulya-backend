<?php

use App\Enums\PaymentCurrency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuidMorphs('payable');
            $table->enum('method', PaymentMethod::cases());
            $table->string('invoice_id');
            $table->enum('currency', PaymentCurrency::cases())->default(PaymentCurrency::RUB);
            $table->decimal('amount', 11, 2);
            $table->enum('status', PaymentStatus::cases())->index();
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('failed_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['method', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
