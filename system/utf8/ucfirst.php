<?php

use Kohana\Support\Utf8;

/**
 * UTF8::ucfirst
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    https://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function _ucfirst($str): string
{
    if (Utf8::isAscii($str))
        return ucfirst($str);

    preg_match('/^(.?)(.*)$/us', $str, $matches);
    return Utf8::strtoupper($matches[1]) . $matches[2];
}
