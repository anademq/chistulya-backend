<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\LinkToken;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildLinkTokenType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildLinkToken',
        'description' => 'A short-lived token that a parent shares with a child to establish a family link.',
        'model' => LinkToken::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the link token record.',
            ],
            'token' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Opaque token string. Pass to linkChildByToken to complete the link.',
            ],
            'expires_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 expiration timestamp. Null if the token has no expiry.',
            ],
        ];
    }
}
