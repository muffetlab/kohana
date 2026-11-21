<?php

namespace Kohana\Session;

use Kohana\Encryption\Encrypter;
use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Log\Logger;

/**
 * Base session class.
 *
 * @package    Kohana
 * @category   Session
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Session
{
    /**
     * @var  string  default session adapter
     */
    public static $default = 'native';

    /**
     * @var  array  session instances
     */
    public static $instances = [];

    /**
     * Creates a singleton session of the given type. Some session types
     * (native, database) also support restarting a session by passing a
     * session id as the second parameter.
     *
     *     $session = Session::instance();
     *
     * [!!] [Session::write] will automatically be called when the request ends.
     *
     * @param string|null $type Type of session (native, cookie, etc.)
     * @param string|null $id Session identifier
     * @return  \Kohana\Session\Session
     * @throws \Kohana\Foundation\KohanaException
     * @uses    \Kohana\Foundation\Application::$config
     */
    public static function instance(?string $type = null, ?string $id = null): self
    {
        if ($type === null) {
            // Use the default type
            $type = static::$default;
        }

        if (!isset(static::$instances[$type])) {
            // Load the configuration for this type
            $config = Application::$config->load('session')->get($type);

            // Set the session class name
            $class = str_contains($type, '\\') ? $type : __NAMESPACE__ . '\\' . ucfirst(strtolower($type)) . 'Session';

            // Create a new session instance
            static::$instances[$type] = $session = new $class($config, $id);

            // Write the session at shutdown
            register_shutdown_function([$session, 'write']);
        }

        return static::$instances[$type];
    }

    /**
     * @var  string  cookie name
     */
    protected $name = 'session';

    /**
     * @var  int  cookie lifetime
     */
    protected $lifetime = 0;

    /**
     * @var  bool  encrypt session data?
     */
    protected $encrypted = false;

    /**
     * @var  array  session data
     */
    protected $data = [];

    /**
     * @var  bool  session destroyed?
     */
    protected $destroyed = false;

    /**
     * Overloads the name, lifetime, and encrypted session settings.
     *
     * [!!] Sessions can only be created using the [Session::instance] method.
     *
     * @param array|null $config configuration
     * @param string|null $id session id
     * @throws \Kohana\Session\SessionException
     * @uses    \Kohana\Session\Session::read
     */
    public function __construct(?array $config = null, ?string $id = null)
    {
        if (isset($config['name'])) {
            // Cookie name to store the session id in
            $this->name = (string) $config['name'];
        }

        if (isset($config['lifetime'])) {
            // Cookie lifetime
            $this->lifetime = (int) $config['lifetime'];
        }

        if (isset($config['encrypted'])) {
            if ($config['encrypted'] === true) {
                // Use the default Encrypt instance
                $config['encrypted'] = 'default';
            }

            // Enable or disable encryption of data
            $this->encrypted = $config['encrypted'];
        }

        // Load the session
        $this->read($id);
    }

    /**
     * Session object is rendered to a serialized string. If encryption is
     * enabled, the session will be encrypted. If not, the output string will
     * be encoded.
     *
     *     echo $session;
     *
     * @return  string
     * @throws \Kohana\Foundation\KohanaException
     * @uses    \Kohana\Encryption\Encrypter::encode
     */
    public function __toString()
    {
        // Serialize the data array
        $data = $this->serializeData($this->data);

        if ($this->encrypted) {
            // Encrypt the data using the default key
            $data = Encrypter::instance($this->encrypted)->encode($data);
        } else {
            // Encode the data
            $data = $this->encode($data);
        }

        return $data;
    }

    /**
     * Returns the current session array. The returned array can also be
     * assigned by reference.
     *
     *     // Get a copy of the current session data
     *     $data = $session->as_array();
     *
     *     // Assign by reference for modification
     *     $data =& $session->as_array();
     *
     * @return  array
     */
    public function & asArray(): array
    {
        return $this->data;
    }

    /**
     * Get the current session id, if the session supports it.
     *
     *     $id = $session->id();
     *
     * [!!] Not all session types have ids.
     *
     * @return  string
     * @since   3.0.8
     */
    public function id(): ?string
    {
        return null;
    }

    /**
     * Get the current session cookie name.
     *
     *     $name = $session->name();
     *
     * @return  string
     * @since   3.0.8
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get a variable from the session array.
     *
     *     $foo = $session->get('foo');
     *
     * @param string $key Variable name
     * @param   mixed   $default    default value to return
     * @return  mixed
     */
    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Get and delete a variable from the session array.
     *
     *     $bar = $session->get_once('bar');
     *
     * @param string $key Variable name
     * @param   mixed   $default    default value to return
     * @return  mixed
     */
    public function getOnce(string $key, $default = null)
    {
        $value = $this->get($key, $default);

        unset($this->data[$key]);

        return $value;
    }

    /**
     * Set a variable in the session array.
     *
     *     $session->set('foo', 'bar');
     *
     * @param string $key Variable name
     * @param   mixed   $value  value
     * @return  $this
     */
    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Set a variable by reference.
     *
     *     $session->bind('foo', $foo);
     *
     * @param string $key Variable name
     * @param   mixed   $value  referenced value
     * @return  $this
     */
    public function bind(string $key, &$value): self
    {
        $this->data[$key] = &$value;

        return $this;
    }

    /**
     * Removes a variable in the session array.
     *
     *     $session->delete('foo');
     *
     * @param string ...$keys variable name
     * @return  $this
     */
    public function delete(...$keys): self
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }

        return $this;
    }

    /**
     * Loads existing session data.
     *
     *     $session->read();
     *
     * @param string|null $id Session ID
     * @return  void
     * @throws \Kohana\Session\SessionException
     */
    public function read(?string $id = null)
    {
        try {
            if (is_string($data = $this->readSession($id))) {
                if ($this->encrypted) {
                    // Decrypt the data using the default key
                    $data = Encrypter::instance($this->encrypted)->decode($data);
                } else {
                    // Decode the data
                    $data = $this->decode($data);
                }

                // Unserialize the data
                $data = $this->unserializeData($data);
            }
        } catch (\Exception $e) {
            // Error reading the session, usually a corrupt session.
            throw new SessionException('Error reading session data.', null, SessionException::SESSION_CORRUPT);
        }

        if (is_array($data)) {
            // Load the data locally
            $this->data = $data;
        }
    }

    /**
     * Generates a new session id and returns it.
     *
     *     $id = $session->regenerate();
     *
     * @return  string
     */
    public function regenerate(): string
    {
        return $this->regenerateSession();
    }

    /**
     * Sets the last_active timestamp and saves the session.
     *
     *     $session->write();
     *
     * [!!] Any errors that occur during session writing will be logged,
     * but not displayed, because sessions are written after output has
     * been sent.
     *
     * @return  bool
     * @uses    \Kohana\Foundation\Application::$log
     */
    public function write(): bool
    {
        if (headers_sent() || $this->destroyed) {
            // Session cannot be written when the headers are sent or when
            // the session has been destroyed
            return false;
        }

        // Set the last active timestamp
        $this->data['last_active'] = time();

        try {
            return $this->writeSession();
        } catch (\Exception $e) {
            // Log & ignore all errors when a write fails
            Application::$log->add(Logger::ERROR, KohanaException::text($e))->write();

            return false;
        }
    }

    /**
     * Completely destroy the current session.
     *
     *     $success = $session->destroy();
     *
     * @return bool
     */
    public function destroy(): bool
    {
        if ($this->destroyed === false) {
            if ($this->destroyed = $this->destroySession()) {
                // The session has been destroyed, clear all data
                $this->data = [];
            }
        }

        return $this->destroyed;
    }

    /**
     * Restart the session.
     *
     *     $success = $session->restart();
     *
     * @return bool
     */
    public function restart(): bool
    {
        if ($this->destroyed === false) {
            // Wipe out the current session.
            $this->destroy();
        }

        // Allow the new session to be saved
        $this->destroyed = false;

        return $this->restartSession();
    }

    /**
     * Serializes the session data.
     *
     * @param array $data Data
     * @return  string
     */
    protected function serializeData(array $data): string
    {
        return serialize($data);
    }

    /**
     * Unserializes the session data.
     *
     * @param string $data Data
     * @return  array
     */
    protected function unserializeData(string $data): array
    {
        return unserialize($data);
    }

    /**
     * Encodes the session data using [base64_encode].
     *
     * @param string $data Data
     * @return  string
     */
    protected function encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * Decodes the session data using [base64_decode].
     *
     * @param string $data Data
     * @return  string
     */
    protected function decode(string $data): string
    {
        return base64_decode($data);
    }

    /**
     * Loads the raw session data string and returns it.
     *
     * @param string|null $id Session ID
     * @return string|array
     */
    abstract protected function readSession(?string $id = null);
    /**
     * Generate a new session id and return it.
     *
     * @return string|null
     */
    abstract protected function regenerateSession(): ?string;
    /**
     * Writes the current session.
     *
     * @return bool
     */
    abstract protected function writeSession(): bool;
    /**
     * Destroys the current session.
     *
     * @return bool
     */
    abstract protected function destroySession(): bool;
    /**
     * Restarts the current session.
     *
     * @return bool
     */
    abstract protected function restartSession(): bool;
}
