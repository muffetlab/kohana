<?php

namespace Kohana\Session;

use Kohana\Cookie\Cookie;

/**
 * Native PHP session class.
 *
 * @package    Kohana
 * @category   Session
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class NativeSession extends Session
{
    /**
     * @return  string
     */
    public function id(): string
    {
        return session_id();
    }

    /**
     * @param string|null $id Session ID
     * @return  null
     */
    protected function readSession(?string $id = null)
    {
        /**
         * session_set_cookie_params will override php ini settings
         * If Cookie::$domain is null or empty and is passed, PHP
         * will override ini and sent cookies with the host name
         * of the server which generated the cookie
         *
         * see issue #3604
         *
         * see https://www.php.net/function.session-set-cookie-params
         * see https://www.php.net/session.configuration#ini.session.cookie-domain
         *
         * set to Cookie::$domain if available, otherwise default to ini setting
         */
        $session_cookie_domain = empty(Cookie::$domain) ? ini_get('session.cookie_domain') : Cookie::$domain;

        // Headers can only be changed before output starts (always true in a web request)
        $headers = !$this->headersSent();

        if ($headers) {
            // Sync up the session cookie with Cookie parameters
            session_set_cookie_params(
                $this->lifetime,
                Cookie::$path,
                $session_cookie_domain,
                Cookie::$secure,
                Cookie::$httponly
            );
        }

        // Keep session files in storage/framework/sessions instead of the system temp directory
        if (!empty($this->config['save_path']) && session_status() !== PHP_SESSION_ACTIVE) {
            is_dir($this->config['save_path']) or mkdir($this->config['save_path'], 0755, true);
            session_save_path($this->config['save_path']);
        }

        if ($headers) {
            // Do not allow PHP to send Cache-Control headers
            session_cache_limiter('');
        }

        // Set the session cookie name
        session_name($this->name);

        if ($id) {
            // Set the session id
            session_id($id);
        }

        // Start the session; without headers (e.g. on the command line) it cannot send a cookie
        session_start($headers ? [] : ['use_cookies' => 0, 'cache_limiter' => '']);

        // Use the $_SESSION global for storing data
        $this->data = &$_SESSION;

        return null;
    }

    /**
     * @return  string
     */
    protected function regenerateSession(): string
    {
        // Regenerate the session id
        session_regenerate_id();

        return session_id();
    }

    /**
     * @return  bool
     */
    protected function writeSession(): bool
    {
        // Write and close the session
        session_write_close();

        return true;
    }

    /**
     * @return  bool
     */
    protected function restartSession(): bool
    {
        // Fire up a new session
        $status = session_start($this->headersSent() ? ['use_cookies' => 0, 'cache_limiter' => ''] : []);

        // Use the $_SESSION global for storing data
        $this->data = &$_SESSION;

        return $status;
    }

    /**
     * @return  bool
     */
    protected function destroySession(): bool
    {
        // Destroy the current session
        session_destroy();

        // Did destruction work?
        $status = !session_id();

        if ($status) {
            // Make sure the session cannot be restarted
            $this->deleteCookie();
        }

        return $status;
    }

}
