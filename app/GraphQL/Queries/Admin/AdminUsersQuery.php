<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Admin;

use App\Enums\ProfileRole;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class AdminUsersQuery extends AdminQuery
{
    protected $attributes = [
        'name' => 'users',
        'description' => 'Returns a paginated list of all users, optionally filtered by profile role.',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('User');
    }

    public function args(): array
    {
        return [
            'profile_role' => [
                'type' => Type::string(),
                'description' => 'Filter users by profile role (e.g. "parent", "child").',
            ],
            'page' => [
                'type' => Type::int(),
                'defaultValue' => 1,
                'description' => 'Page number (1-based). Defaults to 1.',
            ],
            'per_page' => [
                'type' => Type::int(),
                'defaultValue' => 30,
                'description' => 'Number of items per page (max 100). Defaults to 30.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'profile_role' => ['nullable', Rule::in(array_column(ProfileRole::cases(), 'value'))],
        ];
    }

    public function resolve($root, array $args): LengthAwarePaginator
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($args['per_page'] ?? 30)));

        $query = User::query()->with('profile');

        if (! empty($args['profile_role'])) {
            $role = (string) $args['profile_role'];
            $query->whereHas('profile', function ($profileQuery) use ($role): void {
                $profileQuery->where('role', $role);
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
    }
}
