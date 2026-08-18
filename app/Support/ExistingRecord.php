<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class ExistingRecord
{
    /**
     * @template T of Model
     *
     * @param  T|null  $record
     * @return T
     */
    public static function require(?Model $record, string $field): Model
    {
        if ($record === null) {
            throw ValidationException::withMessages([
                $field => __('validation.exists', ['attribute' => $field]),
            ]);
        }

        return $record;
    }
}
