<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Child\ChildPetItem;
use App\Models\PetItem;
use App\Models\PetItemCategory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PetShopService
{
    public const CATEGORIES_CACHE_KEY = 'categories:pet_items';

    public const CATALOG_VERSION_KEY = 'pet_catalog:version';

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function purchase(User $child, string $petItemId): ChildPetItem
    {
        return DB::transaction(function () use ($child, $petItemId): ChildPetItem {
            $petItem = PetItem::available()->whereKey($petItemId)->firstOrFail();

            $requirements = $petItem->requirements ?? [];

            if (! empty($requirements['subscription_required'])) {
                if (! $this->subscriptionService->childHasInheritedSubscription($child)) {
                    throw ValidationException::withMessages([
                        'pet_item_id' => __('validation.in', ['attribute' => 'pet_item_id']),
                    ]);
                }
            }

            $existing = ChildPetItem::where('child_id', $child->id)
                ->where('pet_item_id', $petItem->id)
                ->first();

            if ($existing instanceof ChildPetItem) {
                return $existing;
            }

            $wallet = Wallet::lockForUpdate()->firstOrCreate(['child_id' => $child->id], ['coins' => 0]);
            $price = (int) $petItem->price;

            if ($price > 0 && ! $wallet->hasEnoughCoins($price)) {
                throw ValidationException::withMessages([
                    'coins' => __('validation.custom.coins.insufficient', [
                        'missing' => $price - (int) $wallet->coins,
                    ]),
                ]);
            }

            if ($price > 0) {
                $wallet->debit($price);
            }

            return ChildPetItem::create([
                'child_id' => $child->id,
                'pet_item_id' => $petItem->id,
                'purchased_at' => now(),
            ]);
        });
    }

    public function equip(User $child, string $petItemId): ChildPetItem
    {
        return DB::transaction(function () use ($child, $petItemId): ChildPetItem {
            $childItem = ChildPetItem::lockForUpdate()
                ->where('pet_item_id', $petItemId)
                ->where('child_id', $child->id)
                ->with('petItem')
                ->firstOrFail();

            ChildPetItem::query()
                ->where('child_id', $child->id)
                ->whereHas('petItem', fn (Builder $q) => $q->where('category_id', $childItem->petItem->category_id))
                ->update(['is_equipped' => false]);

            $childItem->forceFill(['is_equipped' => true])->save();

            return $childItem;
        });
    }

    public function unequip(User $child, string $petItemId): ChildPetItem
    {
        $childItem = ChildPetItem::where('pet_item_id', $petItemId)
            ->where('child_id', $child->id)
            ->firstOrFail();

        $childItem->forceFill(['is_equipped' => false])->save();

        return $childItem;
    }

    // Category management (admin only)

    public function createCategory(array $data): PetItemCategory
    {
        $category = PetItemCategory::create([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'order_column' => $data['order_column'] ?? null,
        ]);

        Cache::forget(self::CATEGORIES_CACHE_KEY);

        return $category;
    }

    public function updateCategory(PetItemCategory $category, array $data): PetItemCategory
    {
        $category->update(array_filter([
            'slug' => $data['slug'] ?? null,
            'title' => $data['title'] ?? null,
            'order_column' => $data['order_column'] ?? null,
        ], fn ($v) => $v !== null));

        Cache::forget(self::CATEGORIES_CACHE_KEY);

        return $category->fresh();
    }

    public function deleteCategory(PetItemCategory $category): void
    {
        $category->delete();

        Cache::forget(self::CATEGORIES_CACHE_KEY);
    }
}
