<?php

namespace Kohana\Http\Exceptions;

use Kohana\Foundation\KohanaException;

class Http401Exception extends ExpectedHttpException
{
    /**
     * @var int HTTP 401 Unauthorized
     */
    protected $code = 401;

    /**
     * Specifies the WWW-Authenticate challenge.
     *
     * @param string|null $challenge WWW-Authenticate challenge (e.g. `Basic realm="Control Panel"`)
     */
    public function authenticate(?string $challenge = null)
    {
        if ($challenge === null)
            return $this->headers('www-authenticate');

        $this->headers('www-authenticate', $challenge);

        return $this;
    }

    /**
     * Validate this exception contains everything needed to continue.
     *
     * @throws Kohana_Exception
     * @return bool
     */
    public function check(): bool
    {
        if ($this->headers('www-authenticate') === null)
            throw new KohanaException('A \'www-authenticate\' header must be specified for a HTTP 401 Unauthorized');

        return true;
    }

}
