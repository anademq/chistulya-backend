<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ClaimChallengeRewardPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ClaimChallengeRewardPayload',
        'description' => 'Payload for the claimChallengeReward mutation.',
    ];

    protected function payloadFields(): array
    {
        return [
            'child_challenge' => [
                'type' => GraphQL::type('ChildChallenge'),
                'description' => 'The updated challenge progress record with reward_claimed_at set.',
            ],
            'reward' => [
                'type' => GraphQL::type('RewardGrant'),
                'description' => 'Breakdown of XP and coins granted.',
            ],
        ];
    }
}
