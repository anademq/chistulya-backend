<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class FamilyLinkPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'FamilyLinkPayload',
        'description' => 'Payload returned by family link mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'link' => [
                'type' => GraphQL::type('FamilyLink'),
                'description' => 'The created family link.',
            ],
        ];
    }
}
