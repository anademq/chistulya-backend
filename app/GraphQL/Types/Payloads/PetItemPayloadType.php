<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class PetItemPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'PetItemPayload',
        'description' => 'Payload for admin pet item create and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'pet_item' => [
                'type' => GraphQL::type('PetItem'),
                'description' => 'The created or updated pet item.',
            ],
        ];
    }
}
