<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class SubscriptionPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'SubscriptionPayload',
        'description' => 'Payload returned by subscription upsert mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'subscription' => [
                'type' => GraphQL::type('Subscription'),
                'description' => 'The created or updated subscription plan.',
            ],
        ];
    }
}
