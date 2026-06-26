<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class PaymentPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'PaymentPayload',
        'description' => 'Payload for createSubscriptionPayment and confirmSubscriptionPayment mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'payment' => [
                'type' => GraphQL::type('Payment'),
                'description' => 'The created or confirmed payment record.',
            ],
        ];
    }
}
