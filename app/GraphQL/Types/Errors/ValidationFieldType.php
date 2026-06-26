<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Errors;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ValidationFieldType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ValidationField',
        'description' => 'Validation failures for a single input field.',
    ];

    public function fields(): array
    {
        return [
            'field' => [
                'type' => Type::string(),
                'description' => 'Input field that failed validation, or null for operation-level errors.',
            ],
            'messages' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                'description' => 'All validation messages for this field.',
            ],
        ];
    }
}
