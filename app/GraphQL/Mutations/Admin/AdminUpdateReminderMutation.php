<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\ReminderRepeatPattern;
use App\Enums\ReminderScope;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpdateReminderMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updateReminder',
        'description' => 'Admin: update an existing reminder.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ReminderPayload');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::string())],
            'scope' => ['type' => Type::string()],
            'title' => ['type' => Type::string()],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'time' => ['type' => Type::string()],
            'repeating_pattern' => [
                'type' => Type::string(),
                'description' => 'Recurrence pattern ("once", "daily", or "weekly"). Must be "once" when date is provided.',
            ],
            'date' => [
                'type' => Type::string(),
                'description' => 'Specific date (Y-m-d) for one-time reminders. Requires repeating_pattern = "once".',
            ],
            'repeating_days' => ['type' => Type::listOf(Type::string())],
            'status' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:reminders,id'],
            'scope' => ['nullable', Rule::in(array_column(ReminderScope::cases(), 'value'))],
            'title' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time' => ['nullable', 'date_format:H:i'],
            'repeating_pattern' => [
                'nullable',
                Rule::in(array_column(ReminderRepeatPattern::cases(), 'value')),
                function (string $_attribute, mixed $value, \Closure $fail) use ($args): void {
                    if (filled($args['date'] ?? null) && $value !== null && $value !== ReminderRepeatPattern::ONCE->value) {
                        $fail(__('validation.custom.reminder.date_requires_once'));
                    }
                },
            ],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'repeating_days' => ['nullable', 'array'],
            'repeating_days.*' => ['string'],
            'status' => ['nullable', Rule::in(array_column(ReminderStatus::cases(), 'value'))],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['reminder' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $reminder = Reminder::withTrashed()->whereKey((string) $args['id'])->firstOrFail();

            $fields = array_filter(
                array_intersect_key($args, array_flip(['scope', 'title', 'short_description', 'description', 'time', 'repeating_pattern', 'date', 'repeating_days', 'status'])),
                static fn ($v) => $v !== null,
            );

            if (! empty($fields)) {
                $reminder->forceFill($fields)->save();
            }

            return ['reminder' => $reminder->refresh()];
        });
    }
}
