<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E412 extends HTTP_Exception
{
    /**
     * @var int HTTP 412 Precondition Failed
     */
    protected $_code = 412;
}
