<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Queries\AuthedQuery;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\ReminderService;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MyRemindersQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'myReminders',
        'description' => 'Returns a paginated list of reminders for the given child, optionally filtered by completion status.',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('Reminder');
    }

    public function args(): array
    {
        return [
            'completed' => [
                'type' => Type::boolean(),
                'defaultValue' => false,
                'description' => 'When true, returns only completed reminders. Defaults to false (active reminders).',
            ],
            'page' => [
                'type' => Type::int(),
                'defaultValue' => 1,
                'description' => 'Page number (1-based). Defaults to 1.',
            ],
            'per_page' => [
                'type' => Type::int(),
                'defaultValue' => 20,
                'description' => 'Number of items per page (max 100). Defaults to 20.',
            ],
            'child_id' => [
                'type' => Type::string(),
                'description' => 'Child UUID. Required when called by a parent on behalf of a child; omit when called directly by the child.',
            ],
        ];
    }

    public function resolve($root, array $args): LengthAwarePaginator
    {
        $child = $this->resolveChild($args);

        return app(ReminderService::class)->listForChild(
            $child,
            (bool) ($args['completed'] ?? false),
            max(1, (int) ($args['page'] ?? 1)),
            max(1, min(100, (int) ($args['per_page'] ?? 20)))
        );
    }

    private function resolveChild(array $args): User
    {
        /** @var User $user */
        $user = auth()->user();
        $childId = (string) ($args['child_id'] ?? '');

        if (blank($childId)) {
            app(FamilyService::class)->assertChild($user);

            return $user;
        }

        app(FamilyService::class)->assertParentAccessToChild($user, $childId);

        return User::whereKey($childId)->firstOrFail();
    }
}
