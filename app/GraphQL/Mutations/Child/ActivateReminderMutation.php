<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\ReminderService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ActivateReminderMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'activateReminder',
        'description' => 'Re-activate a completed self-created reminder. Only the child who created the reminder can activate it.',
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
                'description' => 'UUID of the reminder to re-activate.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'reminder_id' => ['required', 'uuid', 'exists:reminders,id'],
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
            return ['reminder' => app(ReminderService::class)->activateByChild($user, $args['reminder_id'])];
        });
    }
}
