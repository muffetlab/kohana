<?php

namespace Kohana\Console;

/**
 * Invalid Task Exception
 *
 * @package    Kohana
 * @category   Minion
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    https://kohana.top/license
 */
class InvalidCommandException extends ConsoleException
{
    public function formatForCli(): string
    {
        return 'ERROR: ' . $this->getMessage() . PHP_EOL;
    }

}
