<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class DailyTaskCategoryPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'DailyTaskCategoryPayload',
        'description' => 'Payload for daily task category create and update mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'category' => [
                'type' => GraphQL::type('DailyTaskCategory'),
                'description' => 'The created or updated daily task category.',
            ],
        ];
    }
}
