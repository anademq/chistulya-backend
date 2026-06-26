<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function createSubscriptionPayment(
        User $user,
        Subscription $subscription,
        PaymentMethod $method,
    ): Payment {
        return Payment::create([
            'user_id' => $user->id,
            'payable_type' => $subscription->getMorphClass(),
            'payable_id' => $subscription->id,
            'invoice_id' => (string) Str::uuid(),
            'method' => $method,
            'currency' => 'rub',
            'amount' => $subscription->price,
            'status' => PaymentStatus::PENDING,
            'expires_at' => now()->addMinutes(30),
            'payload' => [
                'subscription_title' => $subscription->title,
                'subscription_days' => $subscription->duration_days,
            ],
        ]);
    }

    public function confirmPayment(User $user, string $invoiceId, PaymentMethod $method): Payment
    {
        return DB::transaction(function () use ($user, $invoiceId, $method): Payment {
            /** @var Payment|null $payment */
            $payment = Payment::query()
                ->where('user_id', $user->id)
                ->where('invoice_id', $invoiceId)
                ->where('method', $method)
                ->lockForUpdate()
                ->first();

            if (! $payment instanceof Payment) {
                throw ValidationException::withMessages([
                    'invoice_id' => __('validation.custom.payment.invoice_not_found'),
                ]);
            }

            if ($payment->status !== PaymentStatus::PENDING) {
                throw ValidationException::withMessages([
                    'invoice_id' => __('validation.custom.payment.not_pending'),
                ]);
            }

            if ($payment->expires_at->isPast()) {
                $payment->forceFill(['status' => PaymentStatus::EXPIRED])->save();

                throw ValidationException::withMessages([
                    'invoice_id' => __('validation.custom.payment.expired'),
                ]);
            }

            /** @var Subscription $subscription */
            $subscription = $payment->payable;

            $this->subscriptionService->renew($user, $subscription);

            $payment->forceFill([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ])->save();

            return $payment->refresh();
        });
    }
}
