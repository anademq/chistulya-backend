<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Child\ChildPetItem;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildPetItemType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildPetItem',
        'description' => 'A pet item owned by a child, tracking equipped status and purchase date.',
        'model' => ChildPetItem::class,
    ];

    public function fields(): array
    {
        return [
            'pet_item_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the pet item definition.',
            ],
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child who owns this item.',
            ],
            'is_equipped' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether this item is currently equipped on the pet.',
            ],
            'purchased_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the item was purchased. Null if unavailable.',
            ],
            'pet_item' => [
                'type' => GraphQL::type('PetItem'),
                'description' => 'The associated pet item definition.',
            ],
        ];
    }
}
