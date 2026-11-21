<?php

use Kohana\Support\Utf8;

/**
 * UTF8::trim
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    https://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function _trim($str, $charlist = null): string
{
    if ($charlist === null) {
        return trim($str);
    }

    return Utf8::ltrim(Utf8::rtrim($str, $charlist), $charlist);
}
