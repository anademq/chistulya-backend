<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Payment;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PaymentType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Payment',
        'description' => 'A payment invoice record for a subscription purchase.',
        'model' => Payment::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the payment record.',
            ],
            'user_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the parent user who initiated the payment.',
            ],
            'invoice_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'External invoice identifier from the payment gateway.',
            ],
            'method' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Payment method slug (e.g. "card", "yookassa").',
            ],
            'currency' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ISO 4217 currency code (e.g. "RUB", "USD").',
            ],
            'amount' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Payment amount in the specified currency.',
            ],
            'status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Current payment status (e.g. "pending", "paid", "failed").',
            ],
            'expires_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp after which the payment intent expires. Null if not applicable.',
            ],
            'paid_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the payment was successfully confirmed. Null if not yet paid.',
            ],
            'failure_reason' => [
                'type' => Type::string(),
                'description' => 'Human-readable reason for payment failure. Null if the payment succeeded or is still pending.',
            ],
            'subscription' => [
                'type' => GraphQL::type('Subscription'),
                'description' => 'The associated subscription plan definition.',
                'resolve' => static fn (Payment $payment) => $payment->payable,
            ],
        ];
    }
}
