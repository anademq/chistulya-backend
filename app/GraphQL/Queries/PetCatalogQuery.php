<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\PetItem;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class PetCatalogQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'petCatalog',
        'description' => 'Returns a paginated list of pet shop items, optionally filtered by category.',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('PetItem');
    }

    public function args(): array
    {
        return [
            'category_id' => [
                'type' => Type::int(),
                'description' => 'Filter items by category ID.',
            ],
            'page' => [
                'type' => Type::int(),
                'defaultValue' => 1,
                'description' => 'Page number (1-based). Defaults to 1.',
            ],
            'per_page' => [
                'type' => Type::int(),
                'defaultValue' => 10,
                'description' => 'Number of items per page (max 100). Defaults to 10.',
            ],
        ];
    }

    public function resolve($root, array $args): LengthAwarePaginator
    {
        $page = max(1, (int) ($args['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($args['per_page'] ?? 10)));
        $categoryId = isset($args['category_id']) ? (int) $args['category_id'] : null;
        $version = (int) Cache::get(PetShopService::CATALOG_VERSION_KEY, 0);
        $cacheKey = sprintf('graphql:pet_catalog:v%d:%s:%d:%d', $version, $categoryId !== null ? $categoryId : 'all', $page, $perPage);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($categoryId, $page, $perPage): LengthAwarePaginator {
            $query = PetItem::query()->with('category')->orderBy('title');

            if ($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }

            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }
}
