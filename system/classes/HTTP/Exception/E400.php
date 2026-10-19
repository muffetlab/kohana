<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E400 extends HTTP_Exception
{
    /**
     * @var int HTTP 400 Bad Request
     */
    protected $_code = 400;
}
