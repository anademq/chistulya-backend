<?php

declare(strict_types=1);

namespace App\GraphQL\Types\Payloads;

use Rebing\GraphQL\Support\Facades\GraphQL;

class ReminderPayloadType extends PayloadType
{
    protected $attributes = [
        'name' => 'ReminderPayload',
        'description' => 'Payload for reminder creation and management mutations.',
    ];

    protected function payloadFields(): array
    {
        return [
            'reminder' => [
                'type' => GraphQL::type('Reminder'),
                'description' => 'The created or updated reminder. Null when errors occurred.',
            ],
        ];
    }
}
