<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ChildChallengePayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ChildChallengePayload',
        'description' => 'Payload for selectChallenge, startChallenge, and progressChallenge mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'child_challenge' => [
                'type' => GraphQL::type('ChildChallenge'),
                'description' => 'The updated child challenge progress record.',
            ],
        ];
    }
}
