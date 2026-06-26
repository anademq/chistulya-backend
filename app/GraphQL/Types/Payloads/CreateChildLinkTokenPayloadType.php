<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateChildLinkTokenPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'CreateChildLinkTokenPayload',
        'description' => 'Payload for the createChildLinkToken mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'token' => [
                'type' => GraphQL::type('ChildLinkToken'),
                'description' => 'The created link token. Null when errors occurred.',
            ],
        ];
    }
}
