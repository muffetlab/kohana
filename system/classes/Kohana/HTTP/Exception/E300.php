<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception\Redirect as HTTP_Exception_Redirect;

class E300 extends HTTP_Exception_Redirect
{
    /**
     * @var int HTTP 300 Multiple Choices
     */
    protected $_code = 300;
}
