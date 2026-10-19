<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E403 extends HTTP_Exception
{
    /**
     * @var int HTTP 403 Forbidden
     */
    protected $_code = 403;
}
