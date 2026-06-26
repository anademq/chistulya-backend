<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class DailyTaskPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'DailyTaskPayload',
        'description' => 'Payload for daily task create and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'daily_task' => [
                'type' => GraphQL::type('DailyTask'),
                'description' => 'The created or updated daily task.',
            ],
        ];
    }
}
