<?php

namespace Kohana\Auth\Models;

use Kohana\Orm\Model;

/**
 * Default auth role
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    https://kohana.top/license.html
 */
class Role extends Model
{
    // Relationships
    protected $hasMany = [
        'users' => ['model' => User::class, 'through' => 'roles_users'],
    ];

    public function rules(): array
    {
        return [
            'name' => [
                ['not_empty'],
                ['min_length', [':value', 4]],
                ['max_length', [':value', 32]],
            ],
            'description' => [
                ['max_length', [':value', 255]],
            ]
        ];
    }

}
