<?php

namespace Kohana\Http\Exceptions;

use Kohana\Foundation\KohanaException;

class Http305Exception extends ExpectedHttpException
{
    /**
     * @var int HTTP 305 Use Proxy
     */
    protected $code = 305;

    /**
     * Specifies the proxy to replay this request via
     *
     * @param string|null $uri URI of the proxy
     */
    public function location(?string $uri = null)
    {
        if ($uri === null) {
            return $this->headers('Location');
        }

        $this->headers('Location', $uri);

        return $this;
    }

    /**
     * Validate this exception contains everything needed to continue.
     *
     * @throws \Kohana\Foundation\KohanaException
     * @return bool
     */
    public function check(): bool
    {
        if (($location = $this->headers('location')) === null) {
            throw new KohanaException('A \'location\' must be specified for a redirect');
        }

        if (strpos($location, '://') === false) {
            throw new KohanaException('An absolute URI to the proxy server must be specified');
        }

        return true;
    }

}
