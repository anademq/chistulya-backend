<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimAchievementRewardPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ClaimAchievementRewardPayload',
        'description' => 'Payload for the claimAchievementReward mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'child_achievement' => [
                'type' => GraphQL::type('ChildAchievement'),
                'description' => 'The updated achievement record with reward_claimed_at set.',
            ],
            'reward' => [
                'type' => GraphQL::type('RewardGrant'),
                'description' => 'Breakdown of XP and coins granted.',
            ],
        ];
    }
}
