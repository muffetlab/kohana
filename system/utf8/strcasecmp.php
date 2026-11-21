<?php

use Kohana\Support\Utf8;

/**
 * UTF8::strcasecmp
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    https://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function _strcasecmp($str1, $str2): int
{
    if (Utf8::is_ascii($str1) && Utf8::is_ascii($str2))
        return strcasecmp($str1, $str2);

    $str1 = Utf8::strtolower($str1);
    $str2 = Utf8::strtolower($str2);
    return strcmp($str1, $str2);
}
