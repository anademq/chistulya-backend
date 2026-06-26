<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserErrorType extends GraphQLType
{
    protected $attributes = [
        'name' => 'UserError',
        'description' => 'A business-level error tied to a specific input field or the operation as a whole.',
    ];

    public function fields(): array
    {
        return [
            'field' => [
                'type' => Type::string(),
                'description' => 'Input field that caused the error, or null for operation-level errors.',
            ],
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Human-readable description of the error.',
            ],
        ];
    }
}
