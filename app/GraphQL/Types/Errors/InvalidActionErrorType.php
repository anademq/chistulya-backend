<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Errors;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class InvalidActionErrorType extends GraphQLType
{
    protected $attributes = [
        'name' => 'InvalidActionError',
        'description' => 'Returned when the operation is not allowed given the current state.',
    ];

    public function fields(): array
    {
        return [
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Human-readable description of why the action is not allowed.',
            ],
        ];
    }
}
