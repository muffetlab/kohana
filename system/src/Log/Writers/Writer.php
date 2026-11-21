<?php

namespace Kohana\Log\Writers;

use Kohana\Support\Date;

/**
 * Log writer abstract class. All [Log] writers must extend this class.
 *
 * @package    Kohana
 * @category   Logging
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Writer
{
    /**
     * @var  string  timestamp format for log entries.
     *
     * Defaults to Date::$timestamp_format
     */
    public static $timestamp;

    /**
     * @var  string  timezone for log entries
     *
     * Defaults to Date::$timezone, which defaults to date_default_timezone_get()
     */
    public static $timezone;

    /**
     * Numeric log level to string lookup table.
     * @var array
     */
    protected $logLevels = [
        LOG_EMERG => 'EMERGENCY',
        LOG_ALERT => 'ALERT',
        LOG_CRIT => 'CRITICAL',
        LOG_ERR => 'ERROR',
        LOG_WARNING => 'WARNING',
        LOG_NOTICE => 'NOTICE',
        LOG_INFO => 'INFO',
        LOG_DEBUG => 'DEBUG',
    ];

    /**
     * @var  int  Level to use for stack traces
     */
    public static $straceLevel = LOG_DEBUG;

    /**
     * Write an array of messages.
     *
     *     $writer->write($messages);
     *
     * @param   array   $messages
     * @return  void
     */
    abstract public function write(array $messages);
    /**
     * Allows the writer to have a unique key when stored.
     *
     *     echo $writer;
     *
     * @return  string
     */
    final public function __toString()
    {
        return spl_object_hash($this);
    }

    /**
     * Formats a log entry.
     *
     * @param array $message
     * @param string $format
     * @return  string
     * @throws Exception
     */
    public function formatMessage(array $message, string $format = "time --- level: body in file:line"): string
    {
        $message['time'] = Date::formattedTime('@' . $message['time'], static::$timestamp, static::$timezone);
        $message['level'] = $this->logLevels[$message['level']];

        $string = strtr($format, array_filter($message, 'is_scalar'));

        if (isset($message['additional']['exception'])) {
            // Re-use as much as possible, just resetting the body to the trace
            $message['body'] = $message['additional']['exception']->getTraceAsString();
            $message['level'] = $this->logLevels[static::$straceLevel];

            $string .= PHP_EOL . strtr($format, array_filter($message, 'is_scalar'));
        }

        return $string;
    }

}
