<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Errors;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class RateLimitErrorType extends GraphQLType
{
    protected $attributes = [
        'name' => 'RateLimitError',
        'description' => 'Returned when the operation has been rate-limited.',
    ];

    public function fields(): array
    {
        return [
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Human-readable rate-limit message.',
            ],
            'retryAfter' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Seconds until the client may retry.',
            ],
        ];
    }
}
