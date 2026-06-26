<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Child;

use App\GraphQL\Mutations\ChildAuthedMutation;
use App\Models\User;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class CreateChildLinkTokenMutation extends ChildAuthedMutation
{
    protected $attributes = [
        'name' => 'generateFamilyLinkToken',
        'description' => 'Generates a short-lived family link token (QR code payload) that a parent can scan to link themselves to this child account.',
    ];

    public function type(): Type
    {
        return GraphQL::type('CreateChildLinkTokenPayload');
    }

    public function args(): array
    {
        return [
            'ttl_minutes' => ['type' => Type::int(), 'defaultValue' => 60],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['token' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            $token = app(FamilyService::class)->createChildLinkToken(
                $user,
                max(1, (int) ($args['ttl_minutes'] ?? 60))
            );

            return ['token' => $token];
        });
    }
}
