<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\PetItemCategory;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PetItemCategoryType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PetItemCategory',
        'description' => 'A category for pet shop items (e.g. Background, Emoji, Hat).',
        'model' => PetItemCategory::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique ID of the category.',
            ],
            'slug' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'URL-safe identifier slug.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Human-readable category title.',
            ],
            'order_column' => [
                'type' => Type::int(),
                'description' => 'Display order position.',
            ],
        ];
    }
}
