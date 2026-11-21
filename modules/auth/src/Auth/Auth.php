<?php

namespace Kohana\Auth;

use Kohana\Foundation\Application;
use Kohana\Session\Session;

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
     * @return \Kohana\Auth\Auth
     * @throws \Kohana\Foundation\KohanaException
     */
    public static function instance(): self
    {
        if (!isset(static::$instance)) {
            // Load the configuration for this type
            $config = Application::$config->load('auth');

            if (!$type = $config->get('driver')) {
                $type = 'file';
            }

            // Set the authentication class name
            $class = str_contains($type, '\\') ? $type : __NAMESPACE__ . '\\' . ucfirst(strtolower($type)) . 'Auth';

            // Create a new authentication instance
            static::$instance = new $class($config->asArray());
        }

        return static::$instance;
    }

    protected $session;
    protected $config;

    /**
     * Loads Session and configuration options.
     *
     * @param array $config Config Options
     * @return  void
     * @throws \Kohana\Foundation\KohanaException
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
        if (empty($password)) {
            return false;
        }

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
     * Hashes a password with PHP's password_hash(): Argon2id when PHP
     * supports it, bcrypt otherwise. Set `hash_algorithm` and
     * `hash_options` in the auth config to change this.
     *
     *     $hash = $auth->hash($password);
     *
     * @param string $password Plain text password
     * @return string
     */
    public function hash(string $password): string
    {
        return password_hash($password, $this->algorithm(), $this->config['hash_options'] ?? []);
    }

    /**
     * Checks a plain text password against a stored hash. Kohana 3 HMAC
     * hashes (`hash_method` + `hash_key`) are still accepted, so existing
     * users can log in and be rehashed with needsRehash().
     *
     * @param string $password Plain text password
     * @param string|null $hash Stored hash
     * @return bool
     */
    public function verify(string $password, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            return false;
        }

        if (password_get_info($hash)['algo'] !== null) {
            return password_verify($password, $hash);
        }

        // Legacy Kohana 3 hash
        if (empty($this->config['hash_key'])) {
            return false;
        }

        return hash_equals($hash, hash_hmac($this->config['hash_method'] ?? 'sha256', $password, $this->config['hash_key']));
    }

    /**
     * Whether a stored hash should be replaced: it is a legacy HMAC hash or
     * uses other settings than the current algorithm and options.
     *
     * @param string $hash Stored hash
     * @return bool
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm(), $this->config['hash_options'] ?? []);
    }

    /**
     * @return string|int|null Password algorithm for password_hash()
     */
    protected function algorithm()
    {
        return $this->config['hash_algorithm'] ?? (defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);
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
