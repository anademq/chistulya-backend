<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class MutationStatusType extends GraphQLType
{
    protected $attributes = [
        'name' => 'MutationStatus',
        'description' => 'Generic mutation result indicating success or failure with a human-readable message.',
    ];

    public function fields(): array
    {
        return [
            'success' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'True when the operation completed successfully.',
            ],
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Human-readable status message describing the result.',
            ],
        ];
    }
}
