<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Admin;

use App\Models\User;
use App\Models\User\UserSubscription;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class AdminRevokeSubscriptionMutation extends AdminMutation
{
    protected $attributes = [
        'name' => 'revokeUserSubscription',
        'description' => 'Admin: immediately revoke the active subscription for a given user.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'user_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the user whose subscription should be revoked.',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    protected function emptyPayload(): array
    {
        return [];
    }

    public function resolve($root, array $args): array
    {
        return $this->wrapPayload(function () use ($args): array {
            $user = User::whereKey($args['user_id'])->firstOrFail();

            $subscription = UserSubscription::where('user_id', $user->id)
                ->where('expires_at', '>', now())
                ->first();

            if (! $subscription instanceof UserSubscription) {
                throw ValidationException::withMessages([
                    'user_id' => __('validation.custom.subscription.no_active'),
                ]);
            }

            $subscription->update(['auto_renew' => false, 'expires_at' => now()]);

            return [];
        });
    }
}
