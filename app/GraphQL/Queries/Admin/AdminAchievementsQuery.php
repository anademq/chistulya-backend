<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Admin;

use App\Models\Achievement;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminAchievementsQuery extends AdminQuery
{
    protected $attributes = [
        'name' => 'achievements',
        'description' => 'Returns a paginated list of all achievements including soft-deleted records.',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('Achievement');
    }

    public function args(): array
    {
        return [
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

    public function resolve($root, array $args): LengthAwarePaginator
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($args['per_page'] ?? 30)));

        return Achievement::query()
            ->withTrashed()
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
