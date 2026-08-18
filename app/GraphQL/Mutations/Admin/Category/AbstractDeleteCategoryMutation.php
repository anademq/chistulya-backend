<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin\Category;

use App\GraphQL\Mutations\Admin\AdminMutation;
use App\Services\ChallengeService;
use App\Services\DailyTaskService;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

/**
 * Shared logic for admin "delete category" mutations across daily tasks,
 * challenges, and pet items. Deletion is refused while the category still
 * has entities attached (the underlying foreign key restricts deletes).
 */
abstract class AbstractDeleteCategoryMutation extends AdminMutation
{
    abstract protected function categoriesTable(): string;

    /** @return class-string<Model> */
    abstract protected function categoryModel(): string;

    /**
     * Name of the HasMany relation holding the dependent entities
     * (e.g. "dailyTasks", "challenges", "petItems").
     */
    abstract protected function itemsRelation(): string;

    abstract protected function service(): DailyTaskService|ChallengeService|PetShopService;

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID of the category to delete.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'integer', 'exists:'.$this->categoriesTable().',id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            /** @var Model $category */
            $category = ($this->categoryModel())::query()->whereKey($args['id'])->firstOrFail();

            if ($this->hasAttachedItems($category)) {
                throw ValidationException::withMessages([
                    'id' => __('validation.custom.category.has_items'),
                ]);
            }

            $this->service()->deleteCategory($category);

            $this->afterMutation();

            return [];
        });
    }

    /**
     * Determine whether any entities still reference the category. Includes
     * soft-deleted rows, since they physically remain and the restricting
     * foreign key would block deletion regardless of the soft-delete state.
     */
    private function hasAttachedItems(Model $category): bool
    {
        /** @var HasMany<Model, Model> $relation */
        $relation = $category->{$this->itemsRelation()}();

        if (in_array(SoftDeletes::class, class_uses_recursive($relation->getRelated()), true)) {
            return $relation->withTrashed()->exists();
        }

        return $relation->exists();
    }

    /**
     * Hook invoked after a successful delete. Overridden where dependent
     * caches (e.g. the pet catalog) must be invalidated on category changes.
     */
    protected function afterMutation(): void {}
}
