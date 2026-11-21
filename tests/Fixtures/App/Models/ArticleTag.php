<?php

namespace App\Models;

use Kohana\Orm\Model;

class ArticleTag extends Model
{
    // The pivot table is called article_tag, so the alias must differ
    protected $objectName = 'tag';

    protected $tableName = 'article_tags';

    protected $fillable = ['name'];

    protected $hasMany = [
        'articles' => ['model' => Article::class, 'through' => 'article_tag', 'foreign_key' => 'tag_id', 'far_key' => 'article_id'],
    ];
}
