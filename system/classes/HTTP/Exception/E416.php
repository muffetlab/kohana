<?php

namespace Kohana\HTTP\Exception;

use Kohana\HTTP\Exception as HTTP_Exception;

class E416 extends HTTP_Exception
{
    /**
     * @var int HTTP 416 Request Range Not Satisfiable
     */
    protected $_code = 416;
}
