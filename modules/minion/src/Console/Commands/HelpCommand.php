<?php

namespace Kohana\Console\Commands;

use Kohana\Console\Command;
use Kohana\Foundation\Application;
use Kohana\View\View;

/**
 * Help task to display general instructions and list all tasks
 *
 * @package    Kohana
 * @category   Helpers
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    https://kohana.top/license
 */
class HelpCommand extends Command
{
    /**
     * Generates a help list for all tasks
     *
     * @return void
     */
    protected function _execute(array $params)
    {
        $tasks = $this->_compile_task_list($this->_list_task_files());

        $view = new View('minion/help/list');

        $view->set('tasks', $tasks);

        echo $view;
    }

}
