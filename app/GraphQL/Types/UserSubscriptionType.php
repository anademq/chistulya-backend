<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\User\UserSubscription;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class UserSubscriptionType extends GraphQLType
{
    protected $attributes = [
        'name' => 'UserSubscription',
        'description' => 'An active subscription record belonging to a parent user.',
        'model' => UserSubscription::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the subscription record.',
            ],
            'subscription_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the subscription plan.',
            ],
            'user_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the parent user who owns this subscription.',
            ],
            'auto_renew' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether the subscription is configured to renew automatically.',
            ],
            'expires_at' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ISO 8601 expiration timestamp of the subscription.',
            ],
            'subscription' => [
                'type' => GraphQL::type('Subscription'),
                'description' => 'The associated subscription plan definition.',
            ],
        ];
    }
}
