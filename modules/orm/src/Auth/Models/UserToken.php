<?php

namespace Kohana\Auth\Models;

use Kohana\Database\DB;
use Kohana\Orm\Model;
use Kohana\Support\Str;
use Kohana\Validation\Validator;

/**
 * Default auth user toke
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class UserToken extends Model
{
    // Relationships
    protected $belongsTo = [
        'user' => ['model' => User::class],
    ];
    protected $createdColumn = [
        'column' => 'created',
        'format' => true,
    ];

    /**
     * Handles garbage collection and deleting of expired objects.
     *
     * @return  void
     * @throws Kohana_Exception
     */
    public function __construct($id = null)
    {
        parent::__construct($id);

        if (mt_rand(1, 100) === 1) {
            // Do garbage collection
            $this->deleteExpired();
        }

        if ($this->expires < time() && $this->loaded) {
            // This object has expired
            $this->delete();
        }
    }

    /**
     * Deletes all expired tokens.
     *
     * @return  ORM
     * @throws Kohana_Exception
     */
    public function deleteExpired(): Model
    {
        // Delete all expired tokens
        DB::delete($this->tableName)
            ->where('expires', '<', time())
            ->execute($this->db);

        return $this;
    }

    public function create(?Validator $validation = null): Model
    {
        $this->token = $this->createToken();

        return parent::create($validation);
    }

    protected function createToken(): string
    {
        do {
            $token = sha1(uniqid(Str::random('alnum', 32), true));
        } while (Model::factory(static::class, ['token' => $token])->loaded());

        return $token;
    }

}
