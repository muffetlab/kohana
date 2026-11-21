<?php

namespace Kohana\Http\Exceptions;

class Http416Exception extends HttpException
{
    /**
     * @var int HTTP 416 Request Range Not Satisfiable
     */
    protected $_code = 416;

}
