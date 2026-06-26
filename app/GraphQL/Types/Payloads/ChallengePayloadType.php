<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ChallengePayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ChallengePayload',
        'description' => 'Payload for admin challenge create and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'challenge' => [
                'type' => GraphQL::type('Challenge'),
                'description' => 'The created or updated challenge.',
            ],
        ];
    }
}
