<?php

namespace Kohana\Http\Exceptions;

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Routing\Url;

/**
 * Redirect HTTP exception class. Used for all [HTTP_Exception]'s where the status
 * code indicates a redirect.
 *
 * E.g. [HTTP_Exception_301], [HTTP_Exception_302] and most of the other 30x's
 *
 * @package    Kohana
 * @category   Exceptions
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class RedirectHttpException extends ExpectedHttpException
{
    /**
     * Specifies the URI to redirect to.
     *
     * @param string|null $uri URI of the proxy
     * @throws \Kohana\Foundation\KohanaException
     */
    public function location(?string $uri = null)
    {
        if ($uri === null) {
            return $this->headers('Location');
        }

        if (strpos($uri, '://') === false) {
            // Make the URI into a URL
            $uri = Url::site($uri, true, !empty(Application::$indexFile));
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
        if ($this->headers('location') === null) {
            throw new KohanaException('A \'location\' must be specified for a redirect');
        }

        return true;
    }

}
