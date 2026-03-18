<?php

namespace TheFramework\App\Database\Casts;

use TheFramework\Helpers\Collection;

/**
 * Cast an attribute to a Collection instance.
 */
class AsCollection implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (is_null($value)) {
            return new Collection();
        }

        $decoded = json_decode($value, true);

        return new Collection(is_array($decoded) ? $decoded : []);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        return json_encode($value);
    }
}
