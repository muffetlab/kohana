<?php

namespace Kohana\Console;

use Kohana\Foundation\Application;
use Kohana\Support\Arr;
use Kohana\Validation\Validator;
use Kohana\View\View;

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
    public static $taskSeparator = ':';

    /**
     * Converts a task (e.g. db:migrate to a class name)
     *
     * @param string $task Task name
     * @return string Class name
     */
    public static function convertTaskToClassName(string $task): string
    {
        $task = trim($task);

        if (empty($task)) {
            return '';
        }

        // db:migrate => App\Console\Commands\Db\MigrateCommand, falling back to Kohana\Console\Commands
        $class = implode('\\', array_map('ucfirst', explode(static::$taskSeparator, $task))) . 'Command';

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
    public static function convertClassToTask($class): string
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $class = preg_replace('/^(App|Kohana)\\\\Console\\\\Commands\\\\|Command$/', '', $class);

        return strtolower(str_replace('\\', static::$taskSeparator, $class));
    }

    /**
     * Factory for loading minion tasks
     *
     * @param array $options An array of command line options. It should contain the 'task' key
     * @return Minion_Task The Minion task
     * @throws Minion_Exception_InvalidTask
     */
    public static function factory(array $options): self
    {
        if (($task = Arr::get($options, 'task')) !== null) {
            unset($options['task']);
        } elseif (($task = Arr::get($options, 0)) !== null) {
            // The first positional argument (aka 0) may be the task name
            unset($options[0]);
        } else {
            // If we didn't get a valid task, generate the help
            $task = 'help';
        }

        $class = static::convertTaskToClassName($task);

        if (!class_exists($class)) {
            throw new InvalidCommandException("Task ':task' is not a valid minion task", [':task' => $class]);
        }

        $class = new $class();

        if (!$class instanceof self) {
            throw new InvalidCommandException("Task ':task' is not a valid minion task", [':task' => $class]);
        }

        $class->setOptions($options);

        // Show the help page for this task if requested
        if (array_key_exists('help', $options)) {
            $class->method = 'help';
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
    protected $options = [];

    /**
     * Populated with the accepted options for this task.
     * This array is automatically populated based on $_options.
     *
     * @var array
     */
    protected $acceptedOptions = [];
    protected $method = 'handle';

    protected function __construct()
    {
        // Populate $_accepted_options based on keys from $_options
        $this->acceptedOptions = array_keys($this->options);
    }

    /**
     * The file passed to Validation::errors() when validation fails
     * @var string|null
     */
    protected $errorsFile = 'validation';

    /**
     * Gets the task name for the task
     *
     * @return string
     */
    public function __toString()
    {
        static $task_name = null;

        if ($task_name === null) {
            $task_name = static::convertClassToTask($this);
        }

        return $task_name;
    }

    /**
     * Sets options for this task
     *
     * @param array $options The array of options to set
     * @return Kohana_Minion_Task
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * Get the options that were passed into this task with their defaults
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get a set of options that this task can accept
     *
     * @return array
     */
    public function getAcceptedOptions(): array
    {
        return $this->acceptedOptions;
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
    public function buildValidation(Validator $validation): Validator
    {
        // Add a rule to each key making sure it's in the task
        foreach ($validation->data() as $key => $value) {
            $validation->rule($key, [$this, 'validOption'], [':validation', ':field']);
        }

        return $validation;
    }

    /**
     * Returns $_errors_file
     *
     * @return string
     */
    public function getErrorsFile(): ?string
    {
        return $this->errorsFile;
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
        $options = $this->getOptions();

        // Validate $options
        $validation = Validator::factory($options);
        $validation = $this->buildValidation($validation);

        if ($this->method !== 'help' && !$validation->check()) {
            echo View::factory('minion/error/validation')
                ->set('task', static::convertClassToTask($this))
                ->set('errors', $validation->errors($this->getErrorsFile()));
        } else {
            // Finally, run the task
            $method = $this->method;
            echo $this->{$method}($options);
        }
    }

    abstract protected function handle(array $params);

    /**
     * Outputs help for this task
     *
     * @return void
     * @throws View_Exception
     */
    protected function help(array $params)
    {

        $inspector = new \ReflectionClass($this);

        list($description, $tags) = $this->parseDoccomment($inspector->getDocComment());

        $view = View::factory('minion/help/task')
            ->set('description', $description)
            ->set('tags', (array) $tags)
            ->set('task', static::convertClassToTask($this));

        echo $view;
    }

    public function validOption(Validator $validation, $option)
    {
        if (!in_array($option, $this->acceptedOptions)) {
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
    protected function parseDoccomment(string $comment): array
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
    protected function listTaskFiles(): array
    {
        return Arr::merge(Application::listFiles('classes/Console/Commands'), Application::listFiles('src/Console/Commands'));
    }

    protected function compileTaskList(array $files, string $prefix = ''): array
    {
        $output = [];

        foreach ($files as $file => $path) {
            $file = substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1);

            if (is_array($path) && count($path)) {
                $task = $this->compileTaskList($path, $prefix . $file . static::$taskSeparator);

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
