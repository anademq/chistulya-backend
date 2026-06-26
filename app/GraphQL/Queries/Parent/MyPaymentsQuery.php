<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Parent;

use App\Models\Payment;
use App\Models\User;
use GraphQL\Type\Definition\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\GraphQL\Queries\ParentAuthedQuery;

class MyPaymentsQuery extends ParentAuthedQuery
{
    protected $attributes = [
        'name' => 'myPayments',
        'description' => 'Returns a paginated list of payment history for the currently authenticated parent, sorted by creation date descending.',
    ];

    public function type(): Type
    {
        return GraphQL::paginate('Payment');
    }

    public function args(): array
    {
        return [
            'page' => [
                'type' => Type::int(),
                'defaultValue' => 1,
                'description' => 'Page number (1-based). Defaults to 1.',
            ],
            'per_page' => [
                'type' => Type::int(),
                'defaultValue' => 20,
                'description' => 'Number of items per page (max 100). Defaults to 20.',
            ],
        ];
    }

    public function resolve($root, array $args): LengthAwarePaginator
    {
        /** @var User $user */
        $user = auth()->user();
        $page = max(1, (int) ($args['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($args['per_page'] ?? 20)));

        return Payment::query()
            ->where('user_id', $user->id)
            ->with('payable')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
