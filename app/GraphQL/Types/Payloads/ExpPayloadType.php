<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ExpPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ExpPayload',
        'description' => 'Payload for the adminSetChildExp mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'exp' => [
                'type' => GraphQL::type('Exp'),
                'description' => 'The updated experience record.',
            ],
        ];
    }
}
