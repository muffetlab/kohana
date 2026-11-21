<?php

namespace Kohana\Console;

use Kohana\Foundation\KohanaException;

/**
 * Minion exception
 *
 * @package    Kohana
 * @category   Minion
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    https://kohana.top/license
 */
class ConsoleException extends KohanaException
{
    /**
     * Inline exception handler, displays the error message, source of the
     * exception, and the stack trace of the error.
     *
     * Should this display a stack trace? It's useful.
     *
     * @param Throwable $e
     * @return  void
     * @uses    Kohana_Exception::text
     */
    public static function handler(\Throwable $e)
    {
        try {
            // Log the exception
            KohanaException::log($e);

            if ($e instanceof \Kohana\Console\ConsoleException) {
                echo $e->formatForCli();
            } else {
                echo KohanaException::text($e);
            }

            $exit_code = $e->getCode();

            // Never exit "0" after an exception.
            if ($exit_code === 0) {
                $exit_code = 1;
            }

            exit($exit_code);
        } catch (\Exception $e) {
            // Clean the output buffer if one exists
            ob_get_level() and ob_clean();

            // Display the exception text
            echo KohanaException::text($e), "\n";

            // Exit with an error status
            exit(1);
        }
    }

    public function formatForCli(): string
    {
        return KohanaException::text($this);
    }

}
