<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimDailyRewardPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ClaimDailyRewardPayload',
        'description' => 'Payload returned after successfully claiming the daily login reward.',
    ];

    protected function payloadFields(): array
    {
        return [
            'day' => [
                'type' => Type::int(),
                'description' => 'How many consecutive days the user has logged in (1, 2, 3 … and counting). Use this to display the streak counter to the user.',
            ],
            'reward' => [
                'type' => GraphQL::type('DailyReward'),
                'description' => 'The reward tier applied for this claim. reward.day may be 0 when the post-streak fixed reward is in effect.',
            ],
            'grant' => [
                'type' => GraphQL::type('RewardGrant'),
                'description' => 'Breakdown of XP and coins actually granted.',
            ],
        ];
    }
}
