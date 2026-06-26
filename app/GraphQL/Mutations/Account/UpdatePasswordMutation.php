<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\GraphQL\Mutations\AuthedMutation;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Rebing\GraphQL\Support\Facades\GraphQL;

class UpdatePasswordMutation extends AuthedMutation
{
    protected $attributes = [
        'name' => 'updatePassword',
        'description' => 'Update the authenticated user\'s password. Requires the current password for verification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('MutationPayload');
    }

    public function args(): array
    {
        return [
            'current_password' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Current password for verification.',
            ],
            'new_password' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'New password (min 6 characters).',
            ],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            if (! Hash::check($args['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => __('validation.current_password'),
                ]);
            }

            $user->update(['password' => $args['new_password']]);

            return [];
        });
    }
}
