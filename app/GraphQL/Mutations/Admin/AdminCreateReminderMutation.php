<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Enums\ReminderRepeatPattern;
use App\Enums\ReminderScope;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminCreateReminderMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createReminder',
        'description' => 'Admin: create a new reminder.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ReminderPayload');
    }

    public function args(): array
    {
        return [
            'scope' => ['type' => Type::nonNull(Type::string())],
            'title' => ['type' => Type::nonNull(Type::string())],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'time' => ['type' => Type::nonNull(Type::string())],
            'repeating_pattern' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Recurrence pattern ("once", "daily", or "weekly"). Must be "once" when date is provided.',
            ],
            'date' => [
                'type' => Type::string(),
                'description' => 'Specific date (Y-m-d) for one-time reminders. Requires repeating_pattern = "once".',
            ],
            'repeating_days' => ['type' => Type::listOf(Type::string())],
            'status' => ['type' => Type::string(), 'defaultValue' => ReminderStatus::Active->value],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'scope' => ['required', Rule::in(array_column(ReminderScope::cases(), 'value'))],
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time' => ['required', 'date_format:H:i'],
            'repeating_pattern' => [
                'required',
                Rule::in(array_column(ReminderRepeatPattern::cases(), 'value')),
                function (string $_attribute, mixed $value, \Closure $fail) use ($args): void {
                    if (filled($args['date'] ?? null) && $value !== ReminderRepeatPattern::ONCE->value) {
                        $fail(__('validation.custom.reminder.date_requires_once'));
                    }
                },
            ],
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'required_if:repeating_pattern,' . ReminderRepeatPattern::ONCE->value,
            ],
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
        /** @var User $admin */
        $admin = auth()->user();

        return $this->wrapPayload(function () use ($admin, $args): array {
            return ['reminder' => Reminder::create([
                'created_by' => $admin->id,
                'scope' => $args['scope'],
                'title' => $args['title'],
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'time' => $args['time'],
                'repeating_pattern' => $args['repeating_pattern'],
                'date' => $args['date'] ?? null,
                'repeating_days' => $args['repeating_days'] ?? null,
                'status' => $args['status'] ?? ReminderStatus::Active->value,
            ])];
        });
    }
}
