<?php

namespace Kohana\Session;

use Kohana\Cookie\Cookie;

/**
 * Cookie-based session class.
 *
 * @package    Kohana
 * @category   Session
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class CookieSession extends Session
{
    /**
     * @param string|null $id Session ID
     * @return string|null
     * @throws Kohana_Exception
     */
    protected function readSession(?string $id = null): ?string
    {
        return Cookie::get($this->name);
    }

    /**
     * @return  null
     */
    protected function regenerateSession(): ?string
    {
        // Cookie sessions have no id
        return null;
    }

    /**
     * @return  bool
     * @throws Kohana_Exception
     */
    protected function writeSession(): bool
    {
        return Cookie::set($this->name, $this->__toString(), $this->lifetime);
    }

    /**
     * @return  bool
     */
    protected function restartSession(): bool
    {
        return true;
    }

    /**
     * @return  bool
     */
    protected function destroySession(): bool
    {
        return Cookie::delete($this->name);
    }

}
