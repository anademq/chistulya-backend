<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\PetItem;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PetItemType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PetItem',
        'description' => 'A pet shop item available for purchase by child users.',
        'model' => PetItem::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the pet item.',
            ],
            'category_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ID of the item category.',
            ],
            'category' => [
                'type' => GraphQL::type('PetItemCategory'),
                'description' => 'Item category details.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Display title of the item.',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'Brief one-line description. Null if not set.',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Full detailed description. Null if not set.',
            ],
            'media' => [
                'type' => Type::listOf(GraphQL::type('Media')),
                'description' => 'Uploaded media for this item.',
                'resolve' => static fn(PetItem $item): Collection => $item->media,
            ],
            'is_available' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether the item is currently available for purchase.',
            ],
            'price' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Purchase price in coins.',
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the item was created.',
            ],
        ];
    }
}
