<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E505 extends HTTP_Exception
{
    /**
     * @var int HTTP 505 HTTP Version Not Supported
     */
    protected $_code = 505;
}
