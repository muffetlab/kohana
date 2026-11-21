<?php

namespace App\Models;

use Kohana\Orm\Model;

class OrmPost extends Model
{
    protected $_belongs_to = ['author' => ['model' => OrmAuthor::class, 'foreign_key' => 'orm_author_id']];
}
