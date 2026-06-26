<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Profile;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ProfileType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Profile',
        'description' => 'User profile with personal details and role.',
        'model' => Profile::class,
    ];

    public function fields(): array
    {
        return [
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Display name of the user.',
            ],
            'role' => [
                'type' => Type::string(),
                'description' => 'Profile role value ("parent" or "child"). Null until upsertProfile is called.',
                'resolve' => static fn (Profile $profile): ?string => $profile->role?->value,
            ],
            'sex' => [
                'type' => Type::boolean(),
                'description' => 'Biological sex: true = male, false = female. Null if not specified.',
            ],
            'date_of_birth' => [
                'type' => Type::string(),
                'description' => 'Date of birth in YYYY-MM-DD format. Null if not specified.',
            ],
            'city' => [
                'type' => Type::string(),
                'description' => 'City name. Null if not specified.',
            ],
            'timezone' => [
                'type' => Type::string(),
                'description' => 'IANA timezone identifier (e.g. "Europe/Moscow"). Null if not specified.',
            ],
        ];
    }
}
