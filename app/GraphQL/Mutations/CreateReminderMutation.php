<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Enums\ReminderRepeatPattern;
use App\GraphQL\Mutations\AuthedMutation;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\ReminderService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateReminderMutation extends AuthedMutation
{
    protected $attributes = [
        'name' => 'createReminder',
        'description' => 'Create a reminder. A child creates their own reminder; a parent creates one for a specific child by passing child_id.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ReminderPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => [
                'type' => Type::string(),
                'description' => 'Target child UUID. Required when a parent creates a reminder for a child.',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Display title (max 255 characters).',
            ],
            'short_description' => [
                'type' => Type::string(),
                'description' => 'Brief one-line description.',
            ],
            'description' => [
                'type' => Type::string(),
                'description' => 'Full detailed description.',
            ],
            'time' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Trigger time in HH:MM format (24-hour).',
            ],
            'repeating_pattern' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Recurrence pattern ("once", "daily", or "weekly"). Must be "once" when date is provided.',
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
            'child_id' => ['nullable', 'uuid', 'exists:users,id'],
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
            $service = app(ReminderService::class);
            $childId = $args['child_id'] ?? null;

            if ($childId) {
                app(FamilyService::class)->assertParentAccessToChild($user, $childId);
                $child = User::whereKey($childId)->firstOrFail();

                return ['reminder' => $service->createForChild($user, $child, $args)];
            }

            app(FamilyService::class)->assertChild($user);

            return ['reminder' => $service->createSelf($user, $args)];
        });
    }
}
