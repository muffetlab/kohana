<?php

namespace Kohana\Testing;

use Kohana\Foundation\Application;

/**
 * Unit testing helpers
 */
class Helpers
{
    /**
     * Static variable used to work out whether we have an internet
     * connection
     * @see has_internet
     * @var bool
     */
    static protected $hasInternet = null;

    /**
     * Check for internet connectivity
     *
     * @return bool Whether an internet connection is available
     */
    public static function hasInternet(): ?bool
    {
        if (!isset(self::$hasInternet)) {
            // The @ operator is used here to avoid DNS errors when there is no connection.
            $sock = @fsockopen("www.google.com", 80, $errno, $errstr, 1);

            self::$hasInternet = (bool) $sock;
        }

        return self::$hasInternet;
    }

    /**
     * Helper function which replaces the "/" to OS-specific delimiter
     *
     * @param string $path
     * @return string
     */
    static public function dirSeparator(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Removes all cache files from the kohana cache dir
     *
     * @return void
     */
    static public function cleanCacheDir()
    {
        $cache_dir = opendir(Application::$cacheDir);

        while ($dir = readdir($cache_dir)) {
            // Cache files are split into directories based on first two characters of hash
            if ($dir[0] !== '.' && strlen($dir) === 2) {
                $dir = self::dirSeparator(Application::$cacheDir . '/' . $dir . '/');

                $cache = opendir($dir);

                while ($file = readdir($cache)) {
                    if ($file[0] !== '.') {
                        unlink($dir . $file);
                    }
                }

                closedir($cache);

                rmdir($dir);
            }
        }

        closedir($cache_dir);
    }

    /**
     * Backup of the environment variables
     * @see set_environment
     * @var array
     */
    protected $environmentBackup = [];

    /**
     * Allows easy setting & backing up of environment config
     *
     * Option types are checked in the following order:
     *
     * * Server Var
     * * Static Variable
     * * Config option
     *
     * @param array $environment List of environment to set
     * @return false|void
     * @throws Kohana_Exception
     * @throws ReflectionException
     */
    public function setEnvironment(array $environment)
    {
        if (!count($environment))
            return false;

        foreach ($environment as $option => $value) {
            $backup_needed = !array_key_exists($option, $this->environmentBackup);

            // Handle changing superglobals
            if (in_array($option, ['_GET', '_POST', '_SERVER', '_FILES'])) {
                // For some reason we need to do this in order to change the superglobals
                global $$option;

                if ($backup_needed) {
                    $this->environmentBackup[$option] = $$option;
                }

                // PHPUnit makes a backup of superglobals automatically
                $$option = $value;
            }
            // If this is a static property i.e. Html::$windowed_urls
            elseif (strpos($option, '::$') !== false) {
                list($class, $var) = explode('::$', $option, 2);

                $class = new \ReflectionClass($class);

                if ($backup_needed) {
                    $this->environmentBackup[$option] = $class->getStaticPropertyValue($var);
                }

                $class->setStaticPropertyValue($var, $value);
            }
            // If this is an environment variable
            elseif (preg_match('/^[A-Z_-]+$/', $option) || isset($_SERVER[$option])) {
                if ($backup_needed) {
                    $this->environmentBackup[$option] = $_SERVER[$option] ?? '';
                }

                $_SERVER[$option] = $value;
            }
            // Else we assume this is a config option
            else {
                if ($backup_needed) {
                    $this->environmentBackup[$option] = Application::$config->load($option);
                }

                list($group, $var) = explode('.', $option, 2);

                Application::$config->load($group)->set($var, $value);
            }
        }
    }

    /**
     * Restores the environment to the original state
     *
     * @chainable
     * @return void
     * @throws Kohana_Exception
     * @throws ReflectionException
     */
    public function restoreEnvironment()
    {
        $this->setEnvironment($this->environmentBackup);
    }

}
