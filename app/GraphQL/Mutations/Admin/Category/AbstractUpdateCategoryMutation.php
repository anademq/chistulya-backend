<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\GraphQL\Mutations\Admin\AdminMutation;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

/**
 * Shared logic for admin "update category" mutations across daily tasks,
 * challenges, and pet items. Only provided fields are updated.
 */
abstract class AbstractUpdateCategoryMutation extends AdminMutation
{
    abstract protected function payloadType(): string;

    abstract protected function categoriesTable(): string;

    /** @return class-string<Model> */
    abstract protected function categoryModel(): string;

    abstract protected function service(): DailyTaskService|ChallengeService|PetShopService;

    public function type(): Type
    {
        return GraphQL::type($this->payloadType());
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the category to update.',
            ],
            'slug' => [
                'type' => Type::string(),
                'description' => 'New URL-safe identifier slug (lowercase, max 32 chars).',
            ],
            'title' => [
                'type' => Type::string(),
                'description' => 'New human-readable category title (max 64 chars).',
            ],
            'order_column' => [
                'type' => Type::int(),
                'description' => 'New display order position.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'integer', 'exists:'.$this->categoriesTable().',id'],
            'slug' => ['sometimes', 'required', 'string', 'max:32', 'regex:/^[a-z0-9_-]+$/', Rule::unique($this->categoriesTable(), 'slug')->ignore($args['id'] ?? null)],
            'title' => ['sometimes', 'required', 'string', 'max:64'],
            'order_column' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['category' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            /** @var Model $category */
            $category = ($this->categoryModel())::query()->whereKey($args['id'])->firstOrFail();

            $updated = $this->service()->updateCategory($category, $args);

            $this->afterMutation();

            return ['category' => $updated];
        });
    }

    /**
     * Hook invoked after a successful update. Overridden where dependent
     * caches (e.g. the pet catalog) must be invalidated on category changes.
     */
    protected function afterMutation(): void {}
}
