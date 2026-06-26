<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Subscription;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class SubscriptionType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Subscription',
        'description' => 'A subscription plan that parents can purchase to unlock premium features for their children.',
        'model' => Subscription::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the subscription plan.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Display title of the subscription plan.',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'Brief one-line marketing description. Null if not set.',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Full detailed description. Null if not set.',
            ],
            'is_available' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether this plan is currently available for purchase.',
            ],
            'duration_days' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Duration of the subscription in days.',
            ],
            'price' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Price of the subscription in the default currency.',
            ],
        ];
    }
}
