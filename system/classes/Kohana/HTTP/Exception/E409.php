<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E409 extends HTTP_Exception
{
    /**
     * @var int HTTP 409 Conflict
     */
    protected $_code = 409;
}
