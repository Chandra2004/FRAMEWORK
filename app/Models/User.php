<?php

namespace TheFramework\Models;

use TheFramework\App\Database\Model;
use TheFramework\App\Database\Traits\HasFactory;

class User extends Model
{
    use HasFactory;
    protected $table = 'users';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'name',
        'email',
        'password',
        'profile_picture',
        'created_at',
        'updated_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
        'verification_token'
    ];
}
