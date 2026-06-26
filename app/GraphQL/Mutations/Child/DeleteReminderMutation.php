<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class DeleteReminderMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'deleteReminder',
        'description' => 'Delete a self-created reminder. Only the child who created the reminder can delete it.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'reminder_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the reminder to delete.',
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
        return [];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            $reminder = Reminder::where('id', $args['reminder_id'])
                ->where('created_by', $user->id)
                ->firstOrFail();

            app(ReminderService::class)->delete($reminder);

            return [];
        });
    }
}
