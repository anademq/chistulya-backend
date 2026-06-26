<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimDailyTaskRewardPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ClaimDailyTaskRewardPayload',
        'description' => 'Payload for the claimDailyTaskReward mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'child_daily_task' => [
                'type' => GraphQL::type('ChildDailyTask'),
                'description' => 'The updated progress record with reward_claimed_at set.',
            ],
            'reward' => [
                'type' => GraphQL::type('RewardGrant'),
                'description' => 'Breakdown of XP and coins granted.',
            ],
        ];
    }
}
