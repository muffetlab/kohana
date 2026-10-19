<?php

namespace Kohana\HTTP\Exception;

use Kohana\Exception as Kohana_Exception;
use Kohana\HTTP\Exception\Expected as HTTP_Exception_Expected;

class E401 extends HTTP_Exception_Expected
{
    /**
     * @var int HTTP 401 Unauthorized
     */
    protected $_code = 401;

    /**
     * Specifies the WWW-Authenticate challenge.
     *
     * @param string|null $challenge WWW-Authenticate challenge (e.g. `Basic realm="Control Panel"`)
     */
    public function authenticate(string $challenge = null)
    {
        if ($challenge === null)
            return $this->headers('www-authenticate');

        $this->headers('www-authenticate', $challenge);

        return $this;
    }

    /**
     * Validate this exception contains everything needed to continue.
     *
     * @return bool
     * @throws Kohana_Exception
     */
    public function check(): bool
    {
        if ($this->headers('www-authenticate') === null)
            throw new Kohana_Exception('A \'www-authenticate\' header must be specified for a HTTP 401 Unauthorized');

        return true;
    }
}
