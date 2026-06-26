<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildCabinetType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildCabinet',
        'description' => 'Dashboard summary for a child user containing wallet, experience, daily reward progress, and subscription status.',
    ];

    public function fields(): array
    {
        return [
            'wallet' => [
                'type' => Type::nonNull(GraphQL::type('Wallet')),
                'description' => 'Current coin wallet of the child.',
            ],
            'exp' => [
                'type' => Type::nonNull(GraphQL::type('Exp')),
                'description' => 'Current experience and level of the child.',
            ],
            'current_daily_reward_day' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Number of consecutive days the child has logged in and claimed rewards (1, 2, 3 …). Resets to 1 if a day is missed. Use this as the streak counter shown to the user.',
            ],
            'has_active_subscription' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether any linked parent has an active subscription that covers this child.',
            ],
        ];
    }
}
