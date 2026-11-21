<?php

use Kohana\Support\Utf8;

/**
 * UTF8::ucwords
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    https://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function _ucwords($str)
{
    if (Utf8::isAscii($str)) {
        return ucwords($str);
    }

    // [\x0c\x09\x0b\x0a\x0d\x20] matches form feeds, horizontal tabs, vertical tabs, line feeds and carriage returns.
    // This corresponds to the definition of a 'word' defined at https://www.php.net/ucwords
    return preg_replace_callback(
        '/(?<=^|[\x0c\x09\x0b\x0a\x0d\x20])[^\x0c\x09\x0b\x0a\x0d\x20]/u',
        function ($matches) {
            return Utf8::strtoupper($matches[0]);
        },
        $str
    );
}
