<?php

namespace Kohana\Config;

use Kohana\Config\Source as Kohana_Config_Source;

/**
 * Interface for config readers
 *
 * @package    Kohana
 * @category   Configuration
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
interface Reader extends Kohana_Config_Source
{
    /**
     * Tries to load the specified configuration group
     *
     * Returns false if group does not exist or an array if it does
     *
     * @param string $group Configuration group
     * @return bool|array
     */
    public function load(string $group);
}
