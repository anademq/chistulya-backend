<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Account;

use App\GraphQL\Queries\AuthedQuery;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;

class MySessionsQuery extends AuthedQuery
{
    protected $attributes = [
        'name' => 'mySessions',
        'description' => 'Returns all active sessions for the currently authenticated user.',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Session'))));
    }

    public function resolve($root, array $args): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->sessions()
            ->whereNull('revoked_at')
            ->whereHas('refreshTokens', static function ($query): void {
                $query
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now());
            })
            ->withMax(['refreshTokens as refresh_expires_at' => static function ($query): void {
                $query
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now());
            }], 'expires_at')
            ->orderByDesc('last_seen_at')
            ->get();
    }
}
