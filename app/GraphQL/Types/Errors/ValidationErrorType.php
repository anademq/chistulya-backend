<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Errors;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ValidationErrorType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ValidationError',
        'description' => 'All field-level validation failures for a mutation, grouped into a single error object.',
    ];

    public function fields(): array
    {
        return [
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Human-readable summary of the validation failure.',
            ],
            'fields' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('ValidationField')))),
                'description' => 'List of individual field validation failures.',
            ],
        ];
    }
}
