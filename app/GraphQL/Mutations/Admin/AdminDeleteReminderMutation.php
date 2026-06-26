<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Reminder;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminDeleteReminderMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'deleteReminder',
        'description' => 'Admin: soft-delete a reminder.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the reminder to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:reminders,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            Reminder::whereKey($args['id'])->firstOrFail()->delete();

            return [];
        });
    }
}
