<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

class MutationPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'MutationPayload',
        'description' => 'Generic mutation payload for operations that return no specific data.',
    ];

    protected function payloadFields(): array
    {
        return [];
    }
}
