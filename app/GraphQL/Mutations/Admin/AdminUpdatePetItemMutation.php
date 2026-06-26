<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\Media;
use App\Models\PetItem;
use App\Services\MediaService;
use App\Services\PetShopService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Cache;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminUpdatePetItemMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'updatePetItem',
        'description' => 'Admin: update an existing pet shop item.',
    ];

    public function type(): Type
    {
        return GraphQL::type('PetItemPayload');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::string())],
            'category_id' => ['type' => Type::string()],
            'title' => ['type' => Type::string()],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'is_available' => ['type' => Type::boolean()],
            'requirements' => ['type' => Type::string()],
            'price' => ['type' => Type::int()],
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:pet_items,id'],
            'category_id' => ['nullable', 'integer', 'exists:pet_item_categories,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:250'],
            'description' => ['nullable', 'string'],
            'is_available' => ['nullable', 'boolean'],
            'price' => ['nullable', 'integer', 'min:0'],
            'media_id' => ['nullable', 'uuid', 'exists:media,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['pet_item' => null];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $item = PetItem::withTrashed()->whereKey((string) $args['id'])->firstOrFail();

            $fields = array_filter(
                array_intersect_key($args, array_flip(['category_id', 'title', 'short_description', 'description', 'is_available', 'price'])),
                static fn($v) => $v !== null,
            );

            if (array_key_exists('requirements', $args)) {
                $fields['requirements'] = $this->parseRequirements($args['requirements']);
            }

            if (!empty($fields)) {
                $item->forceFill($fields)->save();
            }

            if (!empty($args['media_id'])) {
                $media = Media::whereKey($args['media_id'])->firstOrFail();
                app(MediaService::class)->attachToEntity($media, $item);
            }

            Cache::increment(PetShopService::CATALOG_VERSION_KEY);

            return ['pet_item' => $item->refresh()->load('category')];
        });
    }

    /** @return array<string, mixed>|null */
    private function parseRequirements(mixed $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        $trimmed = trim((string) $raw);

        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);

        return is_array($decoded) ? $decoded : [];
    }
}
