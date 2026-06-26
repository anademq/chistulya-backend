<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class PurchasePetItemPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'PurchasePetItemPayload',
        'description' => 'Payload for the purchasePetItem mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'child_pet_item' => [
                'type' => GraphQL::type('ChildPetItem'),
                'description' => 'The acquired pet item record. Null when errors occurred.',
            ],
        ];
    }
}
