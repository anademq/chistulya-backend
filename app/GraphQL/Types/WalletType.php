<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Wallet;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class WalletType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Wallet',
        'description' => 'Coin wallet belonging to a child user.',
        'model' => Wallet::class,
    ];

    public function fields(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child who owns this wallet.',
            ],
            'coins' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Current coin balance.',
            ],
        ];
    }
}
