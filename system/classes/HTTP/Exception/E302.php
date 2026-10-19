<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception\Redirect as HTTP_Exception_Redirect;

class E302 extends HTTP_Exception_Redirect
{
    /**
     * @var int HTTP 302 Found
     */
    protected $_code = 302;
}
