<?php

namespace App\Support\Requirements\Contracts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

interface JsonRequirements extends Arrayable, Jsonable, JsonSerializable
{
    public static function fromArray(array $data): static;
}
