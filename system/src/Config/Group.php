<?php

namespace Kohana\Config;

/**
 * The group wrapper acts as an interface to all the config directives
 * gathered from across the system.
 *
 * This is the object returned from Kohana_Config::load
 *
 * Any modifications to configuration items should be done through an instance of this object
 *
 * @package    Kohana
 * @category   Configuration
 * @author     Kohana Team
 * @copyright  (c) 2012-2014 Kohana Team
 * @license    https://kohana.top/license
 */
class Group extends \ArrayObject
{
    /**
     * Reference the config object that created this group
     * Used when updating config
     * @var Kohana_Config
     */
    protected $parentInstance = null;

    /**
     * The group this config is for
     * Used when updating config items
     * @var string
     */
    protected $groupName = '';

    /**
     * Constructs the group object.  Kohana_Config passes the config group
     * and its config items to the object here.
     *
     * @param Kohana_Config  $instance "Owning" instance of Kohana_Config
     * @param string         $group    The group name
     * @param array          $config   Group's config
     */
    public function __construct(Repository $instance, $group, array $config = [])
    {
        $this->parentInstance = $instance;
        $this->groupName = $group;

        parent::__construct($config, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Return the current group in serialized form.
     *
     *     echo $config;
     *
     * @return  string
     */
    public function __toString()
    {
        return serialize($this->getArrayCopy());
    }

    /**
     * Alias for getArrayCopy()
     *
     * @return array Array copy of the group's config
     */
    public function asArray(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * Returns the config group's name
     *
     * @return string The group name
     */
    public function groupName(): string
    {
        return $this->groupName;
    }

    /**
     * Get a variable from the configuration or return the default value.
     *
     *     $value = $config->get($key);
     *
     * @param string $key Array key
     * @param   mixed   $default    default value
     * @return  mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
    }

    /**
     * Sets a value in the configuration array.
     *
     *     $config->set($key, $new_value);
     *
     * @param string $key Array key
     * @param   mixed   $value  array value
     * @return  $this
     */
    public function set(string $key, $value): \Kohana\Config\Group
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Overrides ArrayObject::offsetSet()
     * This method is called when config is changed via
     *
     *     $config->var = 'asd';
     *
     *     // OR
     *
     *     $config['var'] = 'asd';
     *
     * @param string $key   The key of the config item we're changing
     * @param mixed  $value The new array value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->parentInstance->writeConfig($this->groupName, $key, $value);

        parent::offsetSet($key, $value);
    }

}
