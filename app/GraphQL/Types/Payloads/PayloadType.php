<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

/**
 * Base class for all mutation payload types.
 * Every payload exposes the shared `success` and `errors` fields,
 * plus any operation-specific fields declared in {@see payloadFields()}.
 */
abstract class PayloadType extends GraphQLType
{
    /**
     * Operation-specific fields to merge alongside success/errors.
     *
     * @return array<string, mixed>
     */
    abstract protected function payloadFields(): array;

    /**
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return array_merge(
            [
                'success' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'True when the operation completed successfully.',
                ],
                'errors' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('MutationError')))),
                    'description' => 'Business-level errors. Empty on success.',
                ],
            ],
            $this->payloadFields(),
        );
    }
}
