<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\User\UserLink;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class FamilyLinkType extends GraphQLType
{
    protected $attributes = [
        'name' => 'FamilyLink',
        'description' => 'Represents a parent–child link between two user accounts.',
        'model' => UserLink::class,
    ];

    public function fields(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child user in this link.',
            ],
            'parent_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the parent user in this link.',
            ],
            'linked_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the link was established.',
            ],
            'child' => [
                'type' => GraphQL::type('User'),
                'description' => 'Resolved child user object.',
            ],
            'parent' => [
                'type' => GraphQL::type('User'),
                'description' => 'Resolved parent user object.',
            ],
        ];
    }
}
