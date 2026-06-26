<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\Child\ChildReminder;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MarkNotificationsAsReadMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'markNotificationsAsRead',
        'description' => 'Mark reminder notifications as seen. Pass notification_ids to mark specific ones, or omit to mark all unseen.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'notification_ids' => [
                'type' => Type::listOf(Type::nonNull(Type::int())),
                'description' => 'List of notification IDs to mark as seen. Omit to mark all unseen.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'notification_ids' => ['nullable', 'array'],
            'notification_ids.*' => ['integer'],
        ];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();
        $ids = $args['notification_ids'] ?? null;

        return $this->wrapPayload(function () use ($user, $ids): array {
            $query = ChildReminder::query()
                ->where('child_id', $user->id)
                ->whereNull('seen_at');

            if ($ids !== null) {
                $query->whereIn('id', $ids);
            }

            $query->update(['seen_at' => now()]);

            return [];
        });
    }
}
