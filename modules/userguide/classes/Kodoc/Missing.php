<?php

namespace Kohana\Kodoc;

/**
 * Set Kodoc_Missing::create_class as an autoloading to prevent missing classes
 * from crashing the api browser.  Classes that are missing a parent will
 * extend this class, and get a warning in the API browser.
 *
 * @package    Kohana/Userguide
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2013 Kohana Team
 * @license    https://kohana.top/license
 * @since      3.0.7
 */
abstract class Missing
{
    /**
     * Creates classes when they are otherwise not found.
     *
     *     Kodoc::create_class('ThisClassDoesNotExist');
     *
     * [!!] All classes created will extend [Kodoc_Missing].
     *
     * @param string $class class name
     * @return bool
     * @since   3.0.7
     */
    public static function create_class(string $class): bool
    {
        // Autoloaders are given the class name without a leading backslash
        $class = ltrim($class, '\\');

        if (!class_exists($class)) {
            if (strpos($class, '\\') !== false) {
                // Create the missing class in its namespace
                $pos = strrpos($class, '\\');

                eval('namespace ' . substr($class, 0, $pos) . '; class ' . substr($class, $pos + 1) . ' extends \Kohana\Kodoc\Missing {}');
            } else {
                // Create a new missing class
                eval("class $class extends \Kohana\Kodoc\Missing {}");
            }
        }

        return true;
    }

}
