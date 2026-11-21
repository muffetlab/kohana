<?php

namespace Kohana\Log\Writers;

use Kohana\Foundation\Application;
use Kohana\Foundation\Debug;
use Kohana\Foundation\KohanaException;

/**
 * File log writer. Writes out messages and stores them in a YYYY/MM directory.
 *
 * @package    Kohana
 * @category   Logging
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class FileWriter extends Writer
{
    /**
     * @var  string  Directory to place log files in
     */
    protected $directory;

    /**
     * Creates a new file logger. Checks that the directory exists and
     * is writable.
     *
     *     $writer = new Log_File($directory);
     *
     * @param string $directory log directory
     * @return  void
     * @throws \Kohana\Foundation\KohanaException
     */
    public function __construct(string $directory)
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new KohanaException('Directory :dir must be writable', [':dir' => Debug::path($directory)]);
        }

        // Determine the directory path
        $this->directory = realpath($directory) . DIRECTORY_SEPARATOR;
    }

    /**
     * Writes each of the messages into the log file. The log file will be
     * appended to the `YYYY/MM/DD.log.php` file, where YYYY is the current
     * year, MM is the current month, and DD is the current day.
     *
     *     $writer->write($messages);
     *
     * @param array $messages
     * @return  void
     * @throws \Exception
     */
    public function write(array $messages)
    {
        // Set the yearly directory name
        $directory = $this->directory . date('Y');

        if (!is_dir($directory)) {
            // Create the yearly directory
            mkdir($directory, 02777);

            // Set permissions (must be manually set to fix umask issues)
            chmod($directory, 02777);
        }

        // Add the month to the directory
        $directory .= DIRECTORY_SEPARATOR . date('m');

        if (!is_dir($directory)) {
            // Create the monthly directory
            mkdir($directory, 02777);

            // Set permissions (must be manually set to fix umask issues)
            chmod($directory, 02777);
        }

        // Set the name of the log file
        $filename = $directory . DIRECTORY_SEPARATOR . date('d') . '.php';

        if (!file_exists($filename)) {
            // Create the log file
            file_put_contents($filename, Application::FILE_SECURITY . ' ?>' . PHP_EOL);

            // Allow anyone to write to log files
            chmod($filename, 0666);
        }

        foreach ($messages as $message) {
            // Write each message into the log file
            file_put_contents($filename, PHP_EOL . $this->formatMessage($message), FILE_APPEND);
        }
    }

}
