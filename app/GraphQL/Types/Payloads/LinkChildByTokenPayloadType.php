<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class LinkChildByTokenPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'LinkChildByTokenPayload',
        'description' => 'Payload for the linkChildByToken mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'link' => [
                'type' => GraphQL::type('FamilyLink'),
                'description' => 'The created family link. Null when errors occurred.',
            ],
        ];
    }
}
