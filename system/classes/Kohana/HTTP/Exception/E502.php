<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E502 extends HTTP_Exception
{
    /**
     * @var int HTTP 502 Bad Gateway
     */
    protected $_code = 502;
}
