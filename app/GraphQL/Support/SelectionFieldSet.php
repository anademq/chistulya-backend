<?php

declare(strict_types=1);

namespace App\GraphQL\Support;

use GraphQL\Type\Definition\ResolveInfo;

class SelectionFieldSet
{
    /**
     * @return array<string, mixed>
     */
    public static function fromInfo(ResolveInfo $info, int $depth = 2): array
    {
        return $info->getFieldSelection($depth);
    }

    /**
     * @param  array<string, mixed>  $selection
     */
    public static function has(array $selection, string $field): bool
    {
        return array_key_exists($field, $selection);
    }
}

