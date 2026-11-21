<?php

namespace Kohana\Config;

use Kohana\Config\Contracts\Writer;
use Kohana\Database\DatabaseException;
use Kohana\Database\DB;

/**
 * Database writer for the config system
 *
 * Schema for configuration table:
 *
 *    CREATE TABLE IF NOT EXISTS `config` (
 *      `group_name` varchar(128) NOT NULL,
 *      `config_key` varchar(128) NOT NULL,
 *      `config_value` text,
 *       PRIMARY KEY (`group_name`,`config_key`)
 *     ) ENGINE=InnoDB;
 *
 * @package    Kohana
 * @category   Configuration
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class DatabaseWriter extends DatabaseReader implements Writer
{
    protected $loadedKeys = [];

    /**
     * Tries to load the specified configuration group
     *
     * Returns false if group does not exist or an array if it does
     *
     * @param string $group Configuration group
     * @return bool|array
     * @throws \Kohana\Foundation\KohanaException
     */
    public function load(string $group)
    {
        $config = parent::load($group);

        if ($config !== false) {
            $this->loadedKeys[$group] = array_combine(array_keys($config), array_keys($config));
        }

        return $config;
    }

    /**
     * Writes the passed config for $group
     *
     * Returns chainable instance on success or throws
     * Kohana_Config_Exception on failure
     *
     * @param string $group The config group
     * @param string $key The config key to write to
     * @param mixed $config The configuration to write
     * @return bool
     * @throws \Kohana\Foundation\KohanaException
     */
    public function write(string $group, string $key, $config): bool
    {
        $config = serialize($config);

        // Check to see if we've loaded the config from the table already
        if (isset($this->loadedKeys[$group][$key])) {
            $this->update($group, $key, $config);
        } else {
            // Attempt to run an insert query
            // This may fail if the config key already exists in the table,
            // and we don't know about it
            try {
                $this->insert($group, $key, $config);
            } catch (DatabaseException $e) {
                // Attempt to run an update instead
                $this->update($group, $key, $config);
            }
        }

        return true;
    }

    /**
     * Insert the config values into the table
     *
     * @param string $group The config group
     * @param string $key The config key to write to
     * @param string $config The serialized configuration to write
     * @return \Kohana\Config\DatabaseWriter
     * @throws \Kohana\Foundation\KohanaException
     */
    protected function insert(string $group, string $key, string $config): self
    {
        DB::insert($this->tableName, ['group_name', 'config_key', 'config_value'])
            ->values([$group, $key, $config])
            ->execute($this->dbInstance);

        return $this;
    }

    /**
     * Update the config values in the table
     *
     * @param string $group The config group
     * @param string $key The config key to write to
     * @param string $config The serialized configuration to write
     * @return \Kohana\Config\DatabaseWriter
     * @throws \Kohana\Foundation\KohanaException
     */
    protected function update(string $group, string $key, string $config): self
    {
        DB::update($this->tableName)
            ->set(['config_value' => $config])
            ->where('group_name', '=', $group)
            ->where('config_key', '=', $key)
            ->execute($this->dbInstance);

        return $this;
    }

}
