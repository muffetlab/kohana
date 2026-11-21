<?php

namespace Kohana\Auth;

use Kohana\Foundation\Application;
use Kohana\Session\Session;
use Kohana\Foundation\KohanaException;

/**
 * User authorization library. Handles user login and logout, as well as secure
 * password hashing.
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Auth
{
    // Auth instances
    protected static $instance;

    /**
     * Singleton pattern
     *
     * @return Auth
     * @throws Kohana_Exception
     */
    public static function instance(): \Kohana\Auth\Auth
    {
        if (!isset(\Kohana\Auth\Auth::$instance)) {
            // Load the configuration for this type
            $config = Application::$config->load('auth');

            if (!$type = $config->get('driver')) {
                $type = 'file';
            }

            // Set the authentication class name
            $class = str_contains($type, '\\') ? $type : __NAMESPACE__ . '\\' . ucfirst(strtolower($type)) . 'Auth';

            // Create a new authentication instance
            \Kohana\Auth\Auth::$instance = new $class($config->asArray());
        }

        return \Kohana\Auth\Auth::$instance;
    }

    protected $session;
    protected $config;

    /**
     * Loads Session and configuration options.
     *
     * @param array $config Config Options
     * @return  void
     * @throws Kohana_Exception
     */
    public function __construct(array $config = [])
    {
        // Save the config in the object
        $this->config = $config;

        $this->session = Session::instance($this->config['session_type']);
    }

    abstract protected function attemptLogin($username, $password, $remember);
    abstract public function password($username);
    abstract public function checkPassword($password);
    /**
     * Gets the currently logged-in user from the session.
     * Returns null if no user is currently logged in.
     *
     * @param   mixed  $default  Default value to return if the user is currently not logged in.
     * @return  mixed
     */
    public function getUser($default = null)
    {
        return $this->session->get($this->config['session_key'], $default);
    }

    /**
     * Attempt to log in a user by using an ORM object and plain-text password.
     *
     * @param string $username Username to log in
     * @param string $password Password to check against
     * @param bool $remember Enable autologin
     * @return  bool
     */
    public function login(string $username, string $password, bool $remember = false): bool
    {
        if (empty($password))
            return false;

        return $this->attemptLogin($username, $password, $remember);
    }

    /**
     * Log out a user by removing the related session variables.
     *
     * @param bool $destroy     Completely destroy the session
     * @param bool $logout_all  Remove all tokens for user
     * @return  bool
     */
    public function logout(bool $destroy = false, bool $logout_all = false): bool
    {
        if ($destroy === true) {
            // Destroy the session completely
            $this->session->destroy();
        } else {
            // Remove the user from the session
            $this->session->delete($this->config['session_key']);

            // Regenerate session_id
            $this->session->regenerate();
        }

        // Double check
        return !$this->loggedIn();
    }

    /**
     * Check if there is an active session. Optionally allows checking for a
     * specific role.
     *
     * @param string|null $role Role name
     * @return  bool
     */
    public function loggedIn(?string $role = null): bool
    {
        return $this->getUser() !== null;
    }

    /**
     * Perform a hmac hash, using the configured method.
     *
     * @param string $str string to hash
     * @return  string
     * @throws Kohana_Exception
     */
    public function hash(string $str): string
    {
        if (!$this->config['hash_key'])
            throw new KohanaException('A valid hash key must be set in your auth config.');

        return hash_hmac($this->config['hash_method'], $str, $this->config['hash_key']);
    }

    protected function completeLogin($user): bool
    {
        // Regenerate session_id
        $this->session->regenerate();

        // Store username in session
        $this->session->set($this->config['session_key'], $user);

        return true;
    }

}
