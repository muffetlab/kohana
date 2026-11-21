<?php

namespace App\Models;

use Kohana\Orm\Model;

class ArticleComment extends Model
{
    protected $guarded = ['id'];

    protected $belongsTo = [
        'article' => ['model' => Article::class, 'foreign_key' => 'article_id'],
    ];
}
