<?php

namespace Kohana\Console\Commands\Key;

use Kohana\Console\Cli;
use Kohana\Console\Command;

/**
 * Generates APP_KEY (encryption) and COOKIE_SALT and writes them to .env.
 *
 * Existing values are kept unless --force is given, because a new APP_KEY
 * makes previously encrypted data unreadable and a new COOKIE_SALT logs
 * everyone out.
 *
 *     php public/index.php --task=key:generate
 *     php public/index.php --task=key:generate --show
 *     php public/index.php --task=key:generate --force
 *
 * Options:
 *   --show   Print new values instead of writing them
 *   --force  Replace values that are already set
 *   --file   Path of the .env file (default: the project root's .env)
 *
 * @package    Kohana/Minion
 * @category   Tasks
 */
class GenerateCommand extends Command
{
    protected $options = [
        'show' => false,
        'force' => false,
        'file' => null,
    ];

    protected function handle(array $params)
    {
        $values = [
            'APP_KEY' => 'base64:' . base64_encode(random_bytes(32)),
            'COOKIE_SALT' => bin2hex(random_bytes(32)),
        ];

        if ($params['show'] !== false) {
            foreach ($values as $name => $value) {
                Cli::write("$name=$value");
            }

            return;
        }

        $file = $params['file'] ?: dirname(DOCROOT) . DIRECTORY_SEPARATOR . '.env';

        foreach (static::write($file, $values, $params['force'] !== false) as $name => $written) {
            Cli::write($written ? "$name set in $file" : "$name is already set in $file; use --force to replace it");
        }
    }

    /**
     * Sets variables in a .env file, creating it from .env.example when it
     * does not exist. Returns, per variable, whether it was written.
     *
     * @param string $file Path of the .env file
     * @param array $values Variable name => value
     * @param bool $force Replace variables that already have a value
     * @return array
     */
    public static function write(string $file, array $values, bool $force = false): array
    {
        if (!is_file($file) && is_file($file . '.example')) {
            copy($file . '.example', $file);
        }

        $contents = is_file($file) ? file_get_contents($file) : '';
        $result = [];

        foreach ($values as $name => $value) {
            $pattern = '/^' . preg_quote($name, '/') . '=(.*)$/m';

            if (preg_match($pattern, $contents, $match)) {
                if (trim($match[1], " \t\"'") !== '' && !$force) {
                    $result[$name] = false;
                    continue;
                }

                $contents = preg_replace($pattern, $name . '=' . addcslashes($value, '\\$'), $contents, 1);
            } else {
                $contents = rtrim($contents, "\n") . ($contents === '' ? '' : "\n") . $name . '=' . $value . "\n";
            }

            $result[$name] = true;
        }

        file_put_contents($file, $contents);

        return $result;
    }
}
