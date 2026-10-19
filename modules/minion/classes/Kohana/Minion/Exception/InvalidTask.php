<?php

namespace Kohana\Minion\Exception;

use Kohana\Minion\Exception as Minion_Exception;

/**
 * Invalid Task Exception
 *
 * @package    Kohana
 * @category   Minion
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    https://kohana.top/license
 */
class InvalidTask extends Minion_Exception
{
    public function format_for_cli(): string
    {
        return 'ERROR: ' . $this->getMessage() . PHP_EOL;
    }

}
