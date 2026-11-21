<?php

namespace Kohana\Console\Commands;

use Kohana\Console\Cli;
use Kohana\Console\Command;

/**
 * Serves the application with PHP's built-in web server, for development.
 *
 *     php kohana serve
 *     php kohana serve --host=0.0.0.0 --port=8080
 *
 * Options:
 *   --host  Address to listen on (default 127.0.0.1)
 *   --port  Port to listen on (default 8000)
 */
class ServeCommand extends Command
{
    protected $options = [
        'host' => '127.0.0.1',
        'port' => '8000',
    ];

    public function buildValidation(\Kohana\Validation\Validator $validation): \Kohana\Validation\Validator
    {
        return parent::buildValidation($validation)
            ->rule('port', 'digit');
    }

    protected function handle(array $params)
    {
        $address = $params['host'] . ':' . $params['port'];

        Cli::write(Cli::color("Kohana development server started on http://$address", 'green'));
        Cli::write('Press Ctrl+C to stop.');

        passthru(implode(' ', array_map('escapeshellarg', [PHP_BINARY, '-S', $address, '-t', DOCROOT, SYSPATH . 'router.php'])), $status);

        return $status;
    }
}
