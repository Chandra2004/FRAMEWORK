<?php

namespace TheFramework\Models;

use TheFramework\App\Model;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'user_id',
        'published_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
