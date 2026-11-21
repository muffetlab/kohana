<?php

namespace Kohana\Session;

use Kohana\Foundation\KohanaException;

/**
 * @package    Kohana
 * @category   Exceptions
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class SessionException extends KohanaException
{
    public const SESSION_CORRUPT = 1;

}
