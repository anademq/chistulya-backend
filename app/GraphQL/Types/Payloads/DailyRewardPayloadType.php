<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class DailyRewardPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'DailyRewardPayload',
        'description' => 'Payload for the adminCreateDailyReward and adminUpdateDailyReward mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'daily_reward' => [
                'type' => GraphQL::type('DailyReward'),
                'description' => 'The created or updated daily reward configuration.',
            ],
        ];
    }
}
