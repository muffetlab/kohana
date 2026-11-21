<?php

namespace App\Models;

use Kohana\Orm\Model;

class OrmAuthor extends Model
{
    protected $hasMany = ['posts' => ['model' => OrmPost::class]];

    public function rules(): array
    {
        return ['name' => [['not_empty']]];
    }
}
