<?php

namespace TheFramework\App\Database\Casts;

/**
 * Cast an attribute to an array-like object (stdClass).
 */
class AsArrayObject implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return is_null($value) ? null : json_decode($value, false);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return json_encode($value);
    }
}
