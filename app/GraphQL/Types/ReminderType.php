<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Enums\ReminderScope;
use App\Models\Reminder;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ReminderType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Reminder',
        'description' => 'A reminder definition with scheduling and recurrence settings.',
        'model' => Reminder::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Unique UUID identifier of the reminder.',
            ],
            'scope' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Raw scope value. Admin use only — clients should use the `source` field instead.',
                'resolve' => static fn(Reminder $reminder): string => $reminder->scope->value,
            ],
            'source' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'How this reminder was created: "global" (system reminder for all), "custom" (from a parent or admin for this child), or "personal" (child created for themselves).',
                'resolve' => static fn(Reminder $reminder): string => match ($reminder->scope) {
                    ReminderScope::GLOBAL => 'global',
                    ReminderScope::CHILD => 'personal',
                    default => 'custom',
                },
            ],
            'creator_id' => [
                'type' => Type::string(),
                'description' => 'UUID of the parent who created this reminder. Null for global/system reminders and admin-created reminders.',
                'resolve' => static function (Reminder $reminder): ?string {
                    if ($reminder->scope === ReminderScope::GLOBAL || $reminder->created_by === null) {
                        return null;
                    }

                    if ($reminder->scope === ReminderScope::CHILD) {
                        return $reminder->created_by;
                    }

                    $reminder->loadMissing('creator');

                    if ($reminder->creator?->isAdminUser()) {
                        return null;
                    }

                    return $reminder->created_by;
                },
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Short display title of the reminder.',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'Brief one-line description. Null if not set.',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Full detailed description. Null if not set.',
            ],
            'time' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Time of day in HH:MM format (24-hour clock).',
            ],
            'repeating_pattern' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Recurrence pattern value ("once", "daily", or "weekly").',
                'resolve' => static fn(Reminder $reminder): string => $reminder->repeating_pattern->value,
            ],
            'repeating_days' => [
                'type' => Type::listOf(Type::string()),
                'description' => 'Days of the week for "weekly" pattern. Null for other patterns.',
            ],
            'status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Reminder status ("active" or "completed").',
                'resolve' => static fn(Reminder $reminder): string => $reminder->status->value,
            ],
            'completed_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the reminder was completed.',
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'ISO 8601 timestamp when the reminder was created.',
            ],
        ];
    }
}
