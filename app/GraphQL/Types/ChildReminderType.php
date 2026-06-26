<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Child\ChildReminder;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ChildReminderType extends GraphQLType
{
    protected $attributes = [
        'name' => 'ChildReminder',
        'description' => 'A reminder notification record for a child.',
        'model' => ChildReminder::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Unique identifier of the notification record.',
            ],
            'reminder_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the reminder definition.',
            ],
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child who owns this notification.',
            ],
            'sent_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the notification was sent.',
            ],
            'seen_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the notification was seen. Null if unread.',
            ],
            'reminder' => [
                'type' => GraphQL::type('Reminder'),
                'description' => 'The associated reminder definition.',
            ],
        ];
    }
}
