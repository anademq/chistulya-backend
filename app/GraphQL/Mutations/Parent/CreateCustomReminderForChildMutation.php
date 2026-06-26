<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\Enums\ReminderRepeatPattern;
use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\ReminderService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateCustomReminderForChildMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'createCustomReminderForChild',
        'description' => 'Parent: create a custom reminder and assign it to a specific child.',
    ];

    public function type(): Type
    {
        return GraphQL::type('ReminderPayload');
    }

    public function args(): array
    {
        return [
            'child_id' => ['type' => Type::nonNull(Type::string())],
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
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'child_id' => ['required', 'uuid', 'exists:users,id'],
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
        app(FamilyService::class)->assertParentAccessToChild($user, $args['child_id']);

        return $this->wrapPayload(function () use ($user, $args): array {
            $child = User::whereKey($args['child_id'])->firstOrFail();
            $reminder = app(ReminderService::class)->createForChild($user, $child, $args);

            return ['reminder' => $reminder];
        });
    }
}
