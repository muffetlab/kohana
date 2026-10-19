<?php

namespace Kohana\Config\File;

use Kohana\Config\Reader as Kohana_Config_Reader;
use Kohana\Kohana as Kohana;
use Kohana\Arr;

/**
 * File-based configuration reader. Multiple configuration directories can be
 * used by attaching multiple instances of this class to [Kohana_Config].
 *
 * @package    Kohana
 * @category   Configuration
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Reader implements Kohana_Config_Reader
{
    /**
     * The directory where config files are located
     * @var string
     */
    protected $_directory = '';

    /**
     * Creates a new file reader using the given directory as a config source
     *
     * @param string $directory Configuration directory to search
     */
    public function __construct(string $directory = 'config')
    {
        // Set the configuration directory name
        $this->_directory = trim($directory, '/');
    }

    /**
     * Load and merge all the configuration files in this group.
     *
     *     $config->load($name);
     *
     * @param string $group Configuration group name
     * @return  array
     * @uses    Kohana::load
     */
    public function load(string $group): array
    {
        $config = [];

        if ($files = Kohana::find_file($this->_directory, $group, null, true)) {
            foreach ($files as $file) {
                // Merge each file to the configuration array
                $config = Arr::merge($config, (array) Kohana::load($file));
            }
        }

        return $config;
    }

}
