<?php

namespace Kohana\Console\Commands;

use Kohana\Console\Command;
use Kohana\View\View;

/**
 * Lists all commands
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
        $tasks = [];
        foreach ($this->compileTaskList($this->listTaskFiles()) as $task) {
            $class = static::convertTaskToClassName($task);

            if (!class_exists($class) || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }

            // First line of the class docblock
            [$description] = $this->parseDoccomment((string) (new \ReflectionClass($class))->getDocComment());
            $tasks[$task] = strtok($description, "\n") ?: '';
        }
        ksort($tasks);

        $view = new View('minion/help/list');

        $view->set('tasks', $tasks);

        echo $view;
    }

}
