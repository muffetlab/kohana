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
    protected function handle(array $params)
    {
        $tasks = $this->compileTaskList($this->listTaskFiles());

        $view = new View('minion/help/list');

        $view->set('tasks', $tasks);

        echo $view;
    }

}
