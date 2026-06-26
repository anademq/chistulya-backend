<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ChildDailyTaskPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ChildDailyTaskPayload',
        'description' => 'Payload for selectDailyTask and completeDailyTask mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'child_daily_task' => [
                'type' => GraphQL::type('ChildDailyTask'),
                'description' => 'The updated child daily task progress record.',
            ],
        ];
    }
}
