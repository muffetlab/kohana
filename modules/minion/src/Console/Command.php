<?php

namespace Kohana\Console;

use Kohana\Support\Arr;
use Kohana\Validation\Validator;
use Kohana\View\View;
use Kohana\Foundation\Application;

/**
 * Interface that all minion tasks must implement
 *
 * @package    Kohana
 * @category   Minion
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Command
{
    /**
     * The separator used to separate different levels of tasks
     * @var string
     */
    public static $task_separator = ':';

    /**
     * Converts a task (e.g. db:migrate to a class name)
     *
     * @param string $task Task name
     * @return string Class name
     */
    public static function convert_task_to_class_name(string $task): string
    {
        $task = trim($task);

        if (empty($task))
            return '';

        // db:migrate => App\Console\Commands\Db\MigrateCommand, falling back to Kohana\Console\Commands
        $class = implode('\\', array_map('ucfirst', explode(static::$task_separator, $task))) . 'Command';

        foreach (['App\\Console\\Commands\\', 'Kohana\\Console\\Commands\\'] as $namespace) {
            if (class_exists($namespace . $class)) {
                return $namespace . $class;
            }
        }

        return 'App\\Console\\Commands\\' . $class;
    }

    /**
     * Gets the task name of a task class / task object
     *
     * @param string|Minion_Task $class The task class / object
     * @return string             The task name
     */
    public static function convert_class_to_task($class): string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $class = preg_replace('/^(App|Kohana)\\\\Console\\\\Commands\\\\|Command$/', '', $class);

        return strtolower(str_replace('\\', static::$task_separator, $class));
    }

    /**
     * Factory for loading minion tasks
     *
     * @param array $options An array of command line options. It should contain the 'task' key
     * @return Minion_Task The Minion task
     * @throws Minion_Exception_InvalidTask
     */
    public static function factory(array $options): \Kohana\Console\Command
    {
        if (($task = Arr::get($options, 'task')) !== null) {
            unset($options['task']);
        } else if (($task = Arr::get($options, 0)) !== null) {
            // The first positional argument (aka 0) may be the task name
            unset($options[0]);
        } else {
            // If we didn't get a valid task, generate the help
            $task = 'help';
        }

        $class = \Kohana\Console\Command::convert_task_to_class_name($task);

        if (!class_exists($class)) {
            throw new InvalidCommandException("Task ':task' is not a valid minion task", [':task' => $class]);
        }

        $class = new $class;

        if (!$class instanceof \Kohana\Console\Command) {
            throw new InvalidCommandException("Task ':task' is not a valid minion task", [':task' => $class]);
        }

        $class->set_options($options);

        // Show the help page for this task if requested
        if (array_key_exists('help', $options)) {
            $class->_method = '_help';
        }

        return $class;
    }

    /**
     * The list of options this task accepts and their default values.
     *
     *     protected $_options = [
     *         'limit' => 4,
     *         'table' => null,
     *     ];
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Populated with the accepted options for this task.
     * This array is automatically populated based on $_options.
     *
     * @var array
     */
    protected $_accepted_options = [];
    protected $_method = '_execute';

    protected function __construct()
    {
        // Populate $_accepted_options based on keys from $_options
        $this->_accepted_options = array_keys($this->_options);
    }

    /**
     * The file passed to Validation::errors() when validation fails
     * @var string|null
     */
    protected $_errors_file = 'validation';

    /**
     * Gets the task name for the task
     *
     * @return string
     */
    public function __toString()
    {
        static $task_name = null;

        if ($task_name === null) {
            $task_name = \Kohana\Console\Command::convert_class_to_task($this);
        }

        return $task_name;
    }

    /**
     * Sets options for this task
     *
     * @param array $options The array of options to set
     * @return Kohana_Minion_Task
     */
    public function set_options(array $options): \Kohana\Console\Command
    {
        foreach ($options as $key => $value) {
            $this->_options[$key] = $value;
        }

        return $this;
    }

    /**
     * Get the options that were passed into this task with their defaults
     *
     * @return array
     */
    public function get_options(): array
    {
        return $this->_options;
    }

    /**
     * Get a set of options that this task can accept
     *
     * @return array
     */
    public function get_accepted_options(): array
    {
        return $this->_accepted_options;
    }

    /**
     * Adds any validation rules/labels for validating _options
     *
     *     public function build_validation(Validation $validation)
     *     {
     *         return parent::build_validation($validation)
     *             ->rule('paramname', 'not_empty'); // Require this param
     *     }
     *
     * @param Validation $validation the validation object to add rules to
     *
     * @return Validation
     */
    public function build_validation(Validator $validation): Validator
    {
        // Add a rule to each key making sure it's in the task
        foreach ($validation->data() as $key => $value) {
            $validation->rule($key, [$this, 'valid_option'], [':validation', ':field']);
        }

        return $validation;
    }

    /**
     * Returns $_errors_file
     *
     * @return string
     */
    public function get_errors_file(): ?string
    {
        return $this->_errors_file;
    }

    /**
     * Execute the task with the specified set of options
     *
     * @return void
     * @throws ReflectionException
     * @throws View_Exception
     */
    public function execute()
    {
        $options = $this->get_options();

        // Validate $options
        $validation = Validator::factory($options);
        $validation = $this->build_validation($validation);

        if ($this->_method !== '_help' && !$validation->check()) {
            echo View::factory('minion/error/validation')
                ->set('task', \Kohana\Console\Command::convert_class_to_task($this))
                ->set('errors', $validation->errors($this->get_errors_file()));
        } else {
            // Finally, run the task
            $method = $this->_method;
            echo $this->{$method}($options);
        }
    }

    abstract protected function _execute(array $params);

    /**
     * Outputs help for this task
     *
     * @return void
     * @throws View_Exception
     */
    protected function _help(array $params)
    {

        $inspector = new \ReflectionClass($this);

        list($description, $tags) = $this->_parse_doccomment($inspector->getDocComment());

        $view = View::factory('minion/help/task')
            ->set('description', $description)
            ->set('tags', (array) $tags)
            ->set('task', \Kohana\Console\Command::convert_class_to_task($this));

        echo $view;
    }

    public function valid_option(Validator $validation, $option)
    {
        if (!in_array($option, $this->_accepted_options)) {
            $validation->error($option, 'minion_option');
        }
    }

    /**
     * Parses a doc comment, extracting both the comment and any tags associated
     *
     * Based on the code in Kodoc::parse()
     *
     * @param string $comment The comment to parse
     * @return array First element is the comment, second is an array of tags
     */
    protected function _parse_doccomment(string $comment): array
    {
        // Normalize all new lines to \n
        $comment = str_replace(["\r\n", "\n"], "\n", $comment);

        // Remove the phpdoc open/close tags and split
        $comment = array_slice(explode("\n", $comment), 1, -1);

        // Tag content
        $tags = [];

        foreach ($comment as $i => $line) {
            // Remove all leading whitespace
            $line = preg_replace('/^\s*\* ?/m', '', $line);

            // Search this line for a tag
            if (preg_match('/^@(\S+)(?:\s*(.+))?$/', $line, $matches)) {
                // This is a tag line
                unset($comment[$i]);

                $name = $matches[1];
                $text = $matches[2] ?? '';

                $tags[$name] = $text;
            } else {
                $comment[$i] = (string) $line;
            }
        }

        $comment = trim(implode("\n", $comment));

        return [$comment, $tags];
    }

    /**
     * Compiles a list of available tasks from a directory structure
     *
     * @param array $files Directory structure of tasks
     * @param string $prefix prefix
     * @return array Compiled tasks
     */
    /**
     * Lists command files from the application (classes/Console/Commands)
     * and from modules and the system (src/Console/Commands).
     *
     * @return array Directory structure of tasks
     */
    protected function _list_task_files(): array
    {
        return Arr::merge(Application::list_files('classes/Console/Commands'), Application::list_files('src/Console/Commands'));
    }

    protected function _compile_task_list(array $files, string $prefix = ''): array
    {
        $output = [];

        foreach ($files as $file => $path) {
            $file = substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1);

            if (is_array($path) && count($path)) {
                $task = $this->_compile_task_list($path, $prefix . $file . \Kohana\Console\Command::$task_separator);

                if ($task) {
                    $output = array_merge($output, $task);
                }
            } else {
                $output[] = strtolower($prefix . preg_replace('/(Command)?\.php$/', '', $file));
            }
        }

        return $output;
    }

}
