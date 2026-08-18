<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class PetItemCategoryPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'PetItemCategoryPayload',
        'description' => 'Payload for pet item category create and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'category' => [
                'type' => GraphQL::type('PetItemCategory'),
                'description' => 'The created or updated pet item category.',
            ],
        ];
    }
}
