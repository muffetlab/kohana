<?php

namespace Kohana\Log\Writers;

/**
 * Syslog log writer.
 *
 * @package    Kohana
 * @category   Logging
 * @author     Jeremy Bush
 * @copyright  (c) 2012 Kohana Team
 * @license    https://kohana.top/license
 */
class SyslogWriter extends Writer
{
    /**
     * @var  string  The syslog identifier
     */
    protected $ident;

    /**
     * Creates a new syslog logger.
     *
     * @link    https://www.php.net/manual/en/function.openlog
     *
     * @param string $ident Syslog identifier
     * @param int $facility Facility to log to
     * @return  void
     */
    public function __construct(string $ident = 'KohanaPHP', int $facility = LOG_USER)
    {
        $this->ident = $ident;

        // Open the connection to syslog
        openlog($this->ident, LOG_CONS, $facility);
    }

    /**
     * Writes each of the messages into the syslog.
     *
     * @param   array   $messages
     * @return  void
     */
    public function write(array $messages)
    {
        foreach ($messages as $message) {
            syslog($message['level'], $message['body']);

            if (isset($message['additional']['exception'])) {
                syslog(Writer::$straceLevel, $message['additional']['exception']->getTraceAsString());
            }
        }
    }

    /**
     * Closes the syslog connection
     *
     * @return  void
     */
    public function __destruct()
    {
        // Close connection to syslog
        closelog();
    }

}
