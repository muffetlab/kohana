<?php

namespace Kohana\Auth;

use Kohana\Support\Arr;

/**
 * File Auth driver.
 * [!!] this Auth driver does not support roles nor autologin.
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class FileAuth extends Auth
{
    // User list
    protected $users;

    /**
     * Constructor loads the user list into the class.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        // Load user list
        $this->users = Arr::get($config, 'users', []);
    }

    /**
     * Logs a user in.
     *
     * @param string $username Username
     * @param string $password Password
     * @param bool $remember Enable autologin (not supported)
     * @return bool
     * @throws \Kohana\Foundation\KohanaException
     */
    protected function attemptLogin($username, $password, $remember): bool
    {
        if (isset($this->users[$username]) && $this->verify((string) $password, $this->users[$username])) {
            // Complete the login
            return $this->completeLogin($username);
        }

        // Login failed
        return false;
    }

    /**
     * Forces a user to be logged in, without specifying a password.
     *
     * @param   mixed    $username  Username
     * @return  bool
     */
    public function forceLogin($username): bool
    {
        // Complete the login
        return $this->completeLogin($username);
    }

    /**
     * Get the stored password for a username.
     *
     * @param   mixed   $username  Username
     * @return  string
     */
    public function password($username): string
    {
        return Arr::get($this->users, $username, false);
    }

    /**
     * Compare password with original (plain text). Works for current (logged in) user
     *
     * @param   string   $password  Password
     * @return  bool
     */
    public function checkPassword($password): bool
    {
        $username = $this->getUser();

        if ($username === null) {
            return false;
        }

        return $this->verify((string) $password, $this->password($username) ?: null);
    }

}
