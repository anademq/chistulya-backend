<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Parent;

use App\Enums\PaymentMethod;
use App\GraphQL\Mutations\ParentAuthedMutation;
use App\Models\User;
use App\Services\PaymentService;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Rule;
use Rebing\GraphQL\Support\Facades\GraphQL;

class ConfirmSubscriptionPaymentMutation extends ParentAuthedMutation
{
    protected $attributes = [
        'name' => 'confirmSubscriptionPayment',
        'description' => 'Confirm a pending subscription payment (internal flow).',
    ];

    public function type(): Type
    {
        return GraphQL::type('PaymentPayload');
    }

    public function args(): array
    {
        return [
            'invoice_id' => ['type' => Type::nonNull(Type::string())],
            'method' => ['type' => Type::string(), 'defaultValue' => PaymentMethod::DEFAULT->value],
        ];
    }

    public function rules(array $args = []): array
    {
        return [
            'invoice_id' => ['required', 'string', 'max:255'],
            'method' => ['nullable', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
        ];
    }

    protected function emptyPayload(): array
    {
        return ['payment' => null];
    }

    public function resolve($root, array $args): array
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->wrapPayload(function () use ($user, $args): array {
            return ['payment' => app(PaymentService::class)->confirmPayment(
                $user,
                (string) $args['invoice_id'],
                PaymentMethod::from((string) ($args['method'] ?? PaymentMethod::DEFAULT->value))
            )];
        });
    }
}
