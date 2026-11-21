<?php

namespace Kohana\Auth;

use Kohana\Auth\Models\User;
use Kohana\Orm\Model;
use Kohana\Http\Request;
use Kohana\Cookie\Cookie;

/**
 * ORM Auth driver.
 *
 * @package    Kohana/Auth
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class OrmAuth extends Auth
{
    /**
     * Checks if a session is active.
     *
     * @param string|null $role Role name string, role ORM object, or array with role names
     * @return bool
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function loggedIn(?string $role = null): bool
    {
        // Get the user from the session
        $user = $this->getUser();

        if (!$user)
            return false;

        if ($user instanceof User && $user->loaded()) {
            // If we don't have a roll no further checking is needed
            if (!$role)
                return true;

            if (is_array($role)) {
                // Get all the roles
                $roles = $this->model('Role')
                    ->where('name', 'IN', $role)
                    ->findAll()
                    ->asArray(null, 'id');

                // Make sure all the roles are valid ones
                if (count($roles) !== count($role))
                    return false;
            }
            else {
                if (!is_object($role)) {
                    // Load the role
                    $roles = $this->model('Role', ['name' => $role]);

                    if (!$roles->loaded())
                        return false;
                }
                else {
                    $roles = $role;
                }
            }

            return $user->has('roles', $roles);
        }

        return false;
    }

    /**
     * Logs a user in.
     *
     * @param $user
     * @param string $password
     * @param bool $remember enable autologin
     * @return bool
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    protected function attemptLogin($user, $password, $remember): bool
    {
        if (!is_object($user)) {
            $username = $user;

            // Load the user
            $user = $this->model('User');
            $user->where($user->uniqueKey($username), '=', $username)->find();
        }

        // If the passwords match, perform a login
        if ($user->loaded() && $this->verify((string) $password, $user->password) && $user->has('roles', $this->model('Role', ['name' => 'login']))) {
            if ($this->needsRehash($user->password)) {
                // Upgrade a legacy or outdated hash; the model's password filter hashes it
                try {
                    $user->password = $password;
                    $user->save();
                } catch (\Exception $e) {
                    // A failed rehash must not block the login
                }
            }

            if ($remember === true) {
                // Token data
                $data = [
                    'user_id' => $user->pk(),
                    'expires' => time() + $this->config['lifetime'],
                    'user_agent' => sha1(Request::$userAgent),
                ];

                // Create a new autologin token
                $token = $this->model('User_Token')
                    ->values($data, array_keys($data))
                    ->create();

                // Set the autologin cookie
                Cookie::set('authautologin', $token->token, $this->config['lifetime']);
            }

            // Finish the login
            $this->completeLogin($user);

            return true;
        }

        // Login failed
        return false;
    }

    /**
     * Forces a user to be logged in, without specifying a password.
     *
     * @param mixed $user username string, or user ORM object
     * @param bool $mark_session_as_forced mark the session as forced
     * @return  void
     * @throws Kohana_Exception
     */
    public function forceLogin($user, bool $mark_session_as_forced = false)
    {
        if (!is_object($user)) {
            $username = $user;

            // Load the user
            $user = $this->model('User');
            $user->where($user->uniqueKey($username), '=', $username)->find();
        }

        if ($mark_session_as_forced === true) {
            // Mark the session as forced, to prevent users from changing account information
            $this->session->set('auth_forced', true);
        }

        // Run the standard completion
        $this->completeLogin($user);
    }

    /**
     * Logs a user in, based on the authautologin cookie.
     *
     * @return  mixed
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function autoLogin()
    {
        if ($token = Cookie::get('authautologin')) {
            // Load the token and user
            $token = $this->model('User_Token', ['token' => $token]);

            if ($token->loaded() && $token->user->loaded()) {
                if ($token->user_agent === sha1(Request::$userAgent)) {
                    // Save the token to create a new unique token
                    $token->save();

                    // Set the new token
                    Cookie::set('authautologin', $token->token, $token->expires - time());

                    // Complete the login with the found data
                    $this->completeLogin($token->user);

                    // Automatic login was successful
                    return $token->user;
                }

                // Token is invalid
                $token->delete();
            }
        }

        return false;
    }

    /**
     * Gets the currently logged-in user from the session (with auto_login check).
     * Returns $default if no user is currently logged in.
     *
     * @param mixed $default to return in case user isn't logged in
     * @return  mixed
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function getUser($default = null)
    {
        $user = parent::getUser($default);

        if ($user === $default) {
            // check for "remembered" login
            if (($user = $this->autoLogin()) === false)
                return $default;
        }

        return $user;
    }

    /**
     * Log a user out and remove any autologin cookies.
     *
     * @param bool $destroy completely destroy the session
     * @param bool $logout_all remove all tokens for user
     * @return bool
     * @throws Kohana_Exception
     */
    public function logout(bool $destroy = false, bool $logout_all = false): bool
    {
        // Set by force_login()
        $this->session->delete('auth_forced');

        if ($token = Cookie::get('authautologin')) {
            // Delete the autologin cookie to prevent re-login
            Cookie::delete('authautologin');

            // Clear the autologin token from the database
            $token = $this->model('User_Token', ['token' => $token]);

            if ($token->loaded() && $logout_all) {
                // Delete all user tokens. This isn't the most elegant solution but does the job
                $tokens = $this->model('User_Token')->where('user_id', '=', $token->user_id)->findAll();

                foreach ($tokens as $_token) {
                    $_token->delete();
                }
            } elseif ($token->loaded()) {
                $token->delete();
            }
        }

        return parent::logout($destroy);
    }

    /**
     * Get the stored password for a username.
     *
     * @param mixed $user username string, or user ORM object
     * @return  string
     * @throws Kohana_Exception
     */
    public function password($user): string
    {
        if (!is_object($user)) {
            $username = $user;

            // Load the user
            $user = $this->model('User');
            $user->where($user->uniqueKey($username), '=', $username)->find();
        }

        return $user->password;
    }

    /**
     * Complete the login for a user by incrementing the logins and setting
     * session data: user_id, username, roles.
     *
     * @param   object  $user  user ORM object
     * @return bool
     */
    protected function completeLogin($user): bool
    {
        $user->completeLogin();

        return parent::completeLogin($user);
    }

    /**
     * Compare password with original (hashed). Works for current (logged in) user
     *
     * @param string $password
     * @return bool
     * @throws Kohana_Exception
     * @throws ORM_Validation_Exception
     * @throws ReflectionException
     */
    public function checkPassword($password): bool
    {
        $user = $this->getUser();

        if (!$user)
            return false;

        return $this->verify((string) $password, $user->password);
    }

    /**
     * Creates an auth model: App\Models\{Name} when the application defines
     * it, otherwise Kohana\Auth\Models\{Name}.
     *
     * @param string $name Model name: User, Role or User_Token
     * @param mixed $id Parameter for find or object to load
     * @return Model
     */
    protected function model(string $name, $id = null): Model
    {
        $name = str_replace('_', '', $name);
        $class = class_exists('App\\Models\\' . $name) ? 'App\\Models\\' . $name : 'Kohana\\Auth\\Models\\' . $name;

        return new $class($id);
    }
}
