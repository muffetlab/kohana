<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception\Redirect as HTTP_Exception_Redirect;

class E307 extends HTTP_Exception_Redirect
{
    /**
     * @var int HTTP 307 Temporary Redirect
     */
    protected $_code = 307;
}
