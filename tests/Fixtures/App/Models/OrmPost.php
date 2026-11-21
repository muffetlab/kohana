<?php

namespace App\Models;

use Kohana\Orm\Model;

class OrmPost extends Model
{
    protected $belongsTo = ['author' => ['model' => OrmAuthor::class, 'foreign_key' => 'orm_author_id']];
}
