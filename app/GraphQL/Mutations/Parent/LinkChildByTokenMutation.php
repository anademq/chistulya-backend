<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\User;
use App\Services\FamilyService;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class LinkChildByTokenMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'linkChildByToken',
        'description' => 'Links a child account to this parent account using a link token created by createChildLinkToken.',
    ];

    public function type(): Type
    {
        return GraphQL::type('LinkChildByTokenPayload');
    }

    public function args(): array
    {
        return [
            'token' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['link' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            $link = app(FamilyService::class)->linkChildByToken($user, $args['token']);

            return ['link' => $link];
        });
    }
}
