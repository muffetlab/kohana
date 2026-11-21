<?php

namespace App\Models;

use Kohana\Orm\Model;

class OrmAuthor extends Model
{
    protected $_has_many = ['posts' => ['model' => OrmPost::class]];

    public function rules(): array
    {
        return ['name' => [['not_empty']]];
    }
}
