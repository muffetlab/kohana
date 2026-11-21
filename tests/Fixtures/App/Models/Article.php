<?php

namespace App\Models;

use Kohana\Orm\Model;

class Article extends Model
{
    protected $timestamps = true;

    protected $fillable = ['title', 'views', 'rating', 'published', 'meta', 'published_at', 'secret'];

    protected $hidden = ['secret'];

    protected $casts = [
        'views' => 'int',
        'rating' => 'float',
        'published' => 'bool',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    protected $hasMany = [
        'comments' => ['model' => ArticleComment::class, 'foreign_key' => 'article_id'],
        'tags' => ['model' => ArticleTag::class, 'through' => 'article_tag', 'foreign_key' => 'article_id', 'far_key' => 'tag_id'],
    ];
}
