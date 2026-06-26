<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Errors;

use App\GraphQL\Errors\InvalidActionError;
use App\GraphQL\Errors\RateLimitError;
use App\GraphQL\Errors\ValidationError;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\UnionType;

class MutationErrorUnionType extends UnionType
{
    protected $attributes = [
        'name' => 'MutationError',
        'description' => 'Union of all business-level error types that can appear in a mutation payload.',
    ];

    public function types(): array
    {
        return [
            GraphQL::type('ValidationError'),
            GraphQL::type('RateLimitError'),
            GraphQL::type('InvalidActionError'),
        ];
    }

    public function resolveType(mixed $root): Type
    {
        return match (true) {
            $root instanceof ValidationError => GraphQL::type('ValidationError'),
            $root instanceof RateLimitError => GraphQL::type('RateLimitError'),
            $root instanceof InvalidActionError => GraphQL::type('InvalidActionError'),
        };
    }
}
