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

class AdminCreatePetItemMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'createPetItem',
        'description' => 'Admin: create a new pet shop item.',
    ];

    public function type(): Type
    {
        return GraphQL::type('PetItemPayload');
    }

    public function args(): array
    {
        return [
            'category_id' => ['type' => Type::nonNull(Type::string())],
            'title' => ['type' => Type::nonNull(Type::string())],
            'short_description' => ['type' => Type::string()],
            'description' => ['type' => Type::string()],
            'is_available' => ['type' => Type::boolean(), 'defaultValue' => true],
            'requirements' => ['type' => Type::string()],
            'price' => ['type' => Type::int(), 'defaultValue' => 0],
            'media_id' => ['type' => Type::string()],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:pet_item_categories,id'],
            'title' => ['required', 'string', 'max:150'],
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
            $item = PetItem::create([
                'category_id' => (int) $args['category_id'],
                'title' => $args['title'],
                'short_description' => $args['short_description'] ?? null,
                'description' => $args['description'] ?? null,
                'is_available' => (bool) ($args['is_available'] ?? true),
                'requirements' => $this->parseRequirements($args['requirements'] ?? null),
                'price' => (int) ($args['price'] ?? 0),
            ]);

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
