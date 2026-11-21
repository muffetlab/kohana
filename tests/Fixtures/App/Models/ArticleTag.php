<?php

namespace App\Models;

use Kohana\Orm\Model;

class ArticleTag extends Model
{
    protected $fillable = ['name'];

    protected $hasMany = [
        'articles' => ['model' => Article::class, 'through' => 'article_tag', 'foreign_key' => 'tag_id', 'far_key' => 'article_id'],
    ];
}
