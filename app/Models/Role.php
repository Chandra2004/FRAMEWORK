<?php

namespace TheFramework\Models;

use TheFramework\App\Database\Model;

/**
 * @method static \TheFramework\App\Database\QueryBuilder query()
 * @method static array all()
 * @method static mixed find($id)
 * @method static mixed where($column, $value)
 * @method static mixed insert(array $data)
 * @method static mixed update(array $data, $id)
 * @method static mixed delete($id)
 * @method static mixed paginate(int $perPage = 10, int $page = 1)
 * @method static static with(array $relations)
 */
class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';

    protected $fillable = [
        // 'name', 'email', ...
    ];

    protected $hidden = [
        // 'password', 'token', ...
    ];
}