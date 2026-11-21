<?php

namespace Kohana\Session;

use Kohana\Cookie\Cookie;
use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Support\Date;

/**
 * Database-based session class.
 *
 * Sample schema:
 *
 *     CREATE TABLE  `sessions` (
 *         `session_id` VARCHAR( 24 ) NOT NULL,
 *         `last_active` INT UNSIGNED NOT NULL,
 *         `contents` TEXT NOT NULL,
 *         PRIMARY KEY ( `session_id` ),
 *         INDEX ( `last_active` )
 *     ) ENGINE = MYISAM ;
 *
 * @package    Kohana/Database
 * @category   Session
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
class DatabaseSession extends Session
{
    // Database instance
    protected $db;
    // Database table name
    protected $table = 'sessions';
    // Database column names
    protected $columns = [
        'session_id' => 'session_id',
        'last_active' => 'last_active',
        'contents' => 'contents',
    ];
    // Garbage collection requests
    protected $gc = 500;
    // The current session id
    protected $sessionId;
    // The old session id
    protected $updateId;

    public function __construct(?array $config = null, $id = null)
    {
        if (!isset($config['group'])) {
            // Use the default group
            $config['group'] = Connection::$default;
        }

        // Load the database
        $this->db = Connection::instance($config['group']);

        if (isset($config['table'])) {
            // Set the table name
            $this->table = (string) $config['table'];
        }

        if (isset($config['gc'])) {
            // Set the gc chance
            $this->gc = (int) $config['gc'];
        }

        if (isset($config['columns'])) {
            // Overload column names
            $this->columns = $config['columns'];
        }

        parent::__construct($config, $id);

        if (mt_rand(0, $this->gc) === $this->gc) {
            // Run garbage collection
            // This will average out to run once every X requests
            $this->gc();
        }
    }

    public function id(): ?string
    {
        return $this->sessionId;
    }

    protected function readSession(?string $id = null)
    {
        if ($id || $id = Cookie::get($this->name)) {
            $result = DB::select([$this->columns['contents'], 'contents'])
                ->from($this->table)
                ->where($this->columns['session_id'], '=', ':id')
                ->limit(1)
                ->param(':id', $id)
                ->execute($this->db);

            if ($result->count()) {
                // Set the current session id
                $this->sessionId = $this->updateId = $id;

                // Return the contents
                return $result->get('contents');
            }
        }

        // Create a new session id
        $this->regenerateSession();

        return null;
    }

    protected function regenerateSession(): string
    {
        // Create the query to find an ID
        $query = DB::select($this->columns['session_id'])
            ->from($this->table)
            ->where($this->columns['session_id'], '=', ':id')
            ->limit(1)
            ->bind(':id', $id);

        do {
            // Create a new session id
            $id = str_replace('.', '-', uniqid('', true));

            // Get the id from the database
            $result = $query->execute($this->db);
        } while ($result->count());

        return $this->sessionId = $id;
    }

    protected function writeSession(): bool
    {
        if ($this->updateId === null) {
            // Insert a new row
            $query = DB::insert($this->table, $this->columns)
                ->values([':new_id', ':active', ':contents']);
        } else {
            // Update the row
            $query = DB::update($this->table)
                ->value($this->columns['last_active'], ':active')
                ->value($this->columns['contents'], ':contents')
                ->where($this->columns['session_id'], '=', ':old_id');

            if ($this->updateId !== $this->sessionId) {
                // Also update the session id
                $query->value($this->columns['session_id'], ':new_id');
            }
        }

        $query
            ->param(':new_id', $this->sessionId)
            ->param(':old_id', $this->updateId)
            ->param(':active', $this->data['last_active'])
            ->param(':contents', $this->__toString());

        // Execute the query
        $query->execute($this->db);

        // The update and the session id are now the same
        $this->updateId = $this->sessionId;

        // Update the cookie with the new session id
        Cookie::set($this->name, $this->sessionId, $this->lifetime);

        return true;
    }

    /**
     * @return  bool
     */
    protected function restartSession(): bool
    {
        $this->regenerateSession();

        return true;
    }

    protected function destroySession(): bool
    {
        if ($this->updateId === null) {
            // Session has not been created yet
            return true;
        }

        // Delete the current session
        $query = DB::delete($this->table)
            ->where($this->columns['session_id'], '=', ':id')
            ->param(':id', $this->updateId);

        try {
            // Execute the query
            $query->execute($this->db);

            // Delete the old session id
            $this->updateId = null;

            // Delete the cookie
            Cookie::delete($this->name);
        } catch (\Exception $e) {
            // An error occurred, the session has not been deleted
            return false;
        }

        return true;
    }

    protected function gc()
    {
        if ($this->lifetime) {
            // Expire sessions when their lifetime is up
            $expires = $this->lifetime;
        } else {
            // Expire sessions after one month
            $expires = Date::MONTH;
        }

        // Delete all sessions that have expired
        DB::delete($this->table)
            ->where($this->columns['last_active'], '<', ':time')
            ->param(':time', time() - $expires)
            ->execute($this->db);
    }

}
