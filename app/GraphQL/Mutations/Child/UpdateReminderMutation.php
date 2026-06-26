<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\Enums\ReminderRepeatPattern;
use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class UpdateReminderMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'updateReminder',
        'description' => 'Update a self-created reminder. Only the child who created the reminder can update it.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ReminderPayload');
    }

    public function args(): array
    {
        return [
            'reminder_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the reminder to update.',
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'New display title (max 255 characters).',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'New brief one-line description.',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'New full detailed description.',
            ],
            'time' => [
                'type' => Type::string(),
                'description' => 'New trigger time in HH:MM format (24-hour).',
            ],
            'repeating_pattern' => [
                'type' => Type::string(),
                'description' => 'New recurrence pattern ("once", "daily", or "weekly"). Must be "once" when date is provided.',
            ],
            'date' => [
                'type' => Type::string(),
                'description' => 'Specific date (Y-m-d) for one-time reminders. Requires repeating_pattern = "once".',
            ],
            'repeating_days' => [
                'type' => Type::listOf(Type::string()),
                'description' => 'Days of week for "weekly" pattern.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'reminder_id' => ['required', 'uuid', 'exists:reminders,id'],
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
        ];
    }

    protected function emptyPayload(): array
    {
        return ['reminder' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            $reminder = Reminder::where('id', $args['reminder_id'])
                ->where('created_by', $user->id)
                ->firstOrFail();

            return ['reminder' => app(ReminderService::class)->update($user, $reminder, $args)];
        });
    }
}
