<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Exp;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ExpType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Exp',
        'description' => 'Experience and level progression for a child user.',
        'model' => Exp::class,
    ];

    public function fields(): array
    {
        return [
            'child_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'UUID of the child who owns this experience record.',
            ],
            'level' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Current level of the child.',
            ],
            'xp' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Total experience points accumulated.',
            ],
        ];
    }
}
