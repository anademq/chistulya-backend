<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\GraphQL\Mutations\Admin\AdminMutation;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

/**
 * Shared logic for admin "create category" mutations across daily tasks,
 * challenges, and pet items. Concrete classes only declare their target
 * table, payload type, and backing service.
 */
abstract class AbstractCreateCategoryMutation extends AdminMutation
{
    abstract protected function payloadType(): string;

    abstract protected function categoriesTable(): string;

    abstract protected function service(): DailyTaskService|ChallengeService|PetShopService;

    public function type(): Type
    {
        return GraphQL::type($this->payloadType());
    }

    public function args(): array
    {
        return [
            'slug' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'URL-safe unique identifier slug (lowercase, max 32 chars).',
            ],
            'title' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Human-readable category title (max 64 chars).',
            ],
            'order_column' => [
                'type' => Type::int(),
                'description' => 'Display order position. Lower values appear first.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'slug' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_-]+$/', 'unique:'.$this->categoriesTable().',slug'],
            'title' => ['required', 'string', 'max:64'],
            'order_column' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['category' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(fn (): array => [
            'category' => $this->service()->createCategory($args),
        ]);
    }
}
