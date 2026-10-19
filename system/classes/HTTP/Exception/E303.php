<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception\Redirect as HTTP_Exception_Redirect;

class E303 extends HTTP_Exception_Redirect
{
    /**
     * @var int HTTP 303 See Other
     */
    protected $_code = 303;
}
