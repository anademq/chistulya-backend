<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class WalletPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'WalletPayload',
        'description' => 'Payload for the adminSetChildCoins mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'wallet' => [
                'type' => GraphQL::type('Wallet'),
                'description' => 'The updated coin wallet record.',
            ],
        ];
    }
}
