<?php

use Kohana\Support\Utf8;

/**
 * UTF8::rtrim
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    https://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function _rtrim($str, $charlist = null)
{
    if ($charlist === null)
        return rtrim($str);

    if (Utf8::isAscii($charlist))
        return rtrim($str, $charlist);

    $charlist = preg_replace('#[-\[\]:\\\\^/]#', '\\\\$0', $charlist);

    return preg_replace('/[' . $charlist . ']++$/uD', '', $str);
}
