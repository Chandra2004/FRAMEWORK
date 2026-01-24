<?php

namespace TheFramework\Models;

use TheFramework\App\Model;

class User extends Model
{
    protected $table = 'users'; // Explicitly define table, though Model might auto-detect 'users' from 'User'
    protected $primaryKey = 'uid'; // Explicitly define primary key

    protected $fillable = [
        'uid',
        'name',
        'email',
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
