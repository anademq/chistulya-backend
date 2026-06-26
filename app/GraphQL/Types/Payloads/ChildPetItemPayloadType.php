<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ChildPetItemPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ChildPetItemPayload',
        'description' => 'Payload for equipPetItem, unequipPetItem, and adminGrantPetItemToChild mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'child_pet_item' => [
                'type' => GraphQL::type('ChildPetItem'),
                'description' => 'The updated child pet item record.',
            ],
        ];
    }
}
