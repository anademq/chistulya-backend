<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class AchievementPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'AchievementPayload',
        'description' => 'Payload for admin achievement create and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'achievement' => [
                'type' => GraphQL::type('Achievement'),
                'description' => 'The created or updated achievement.',
            ],
        ];
    }
}
