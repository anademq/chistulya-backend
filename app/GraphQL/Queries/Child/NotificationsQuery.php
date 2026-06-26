<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Child;

use App\GraphQL\Queries\ChildAuthedQuery;
use App\Models\Child\ChildReminder;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;

class NotificationsQuery extends ChildAuthedQuery
{
    protected $attributes = [
        'name' => 'notifications',
        'description' => 'Returns paginated reminder notifications for the authenticated child. Pass unread_only=true to fetch only notifications missed while offline.',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('ChildReminder');
    }

    public function args(): array
    {
        return [
            'unread_only' => [
                'type' => Type::boolean(),
                'defaultValue' => false,
                'description' => 'When true returns only unseen notifications. Defaults to false.',
            ],
            'page' => [
                'type' => Type::int(),
                'defaultValue' => 1,
                'description' => 'Page number (1-based). Defaults to 1.',
            ],
            'per_page' => [
                'type' => Type::int(),
                'defaultValue' => 20,
                'description' => 'Items per page (max 100). Defaults to 20.',
            ],
        ];
    }

    public function resolve($root, array $args): LengthAwarePaginator
    {
        /** @var User $user */
        $user = auth()->user();

        $page = max(1, (int) ($args['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($args['per_page'] ?? 20)));

        $query = ChildReminder::query()
            ->with('reminder')
            ->where('child_id', $user->id)
            ->orderByDesc('sent_at');

        if ($args['unread_only'] ?? false) {
            $query->unread();
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
