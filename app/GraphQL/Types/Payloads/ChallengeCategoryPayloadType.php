<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ChallengeCategoryPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ChallengeCategoryPayload',
        'description' => 'Payload for challenge category create and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'category' => [
                'type' => GraphQL::type('ChallengeCategory'),
                'description' => 'The created or updated challenge category.',
            ],
        ];
    }
}
