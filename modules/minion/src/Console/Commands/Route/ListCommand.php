<?php

namespace Kohana\Console\Commands\Route;

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Routing\Route;

/**
 * Lists the registered routes in matching order.
 *
 *     php kohana route:list
 */
class ListCommand extends Command
{
    protected function handle(array $params)
    {
        $rows = [['Name', 'URI pattern', 'Controller', 'Action', 'Directory']];

        foreach (Route::all() as $name => $route) {
            $defaults = $route->defaults();
            $controller = $defaults['controller'] ?? '';

            if ($controller !== '' && !str_contains($controller, '\\')) {
                $controller = ucfirst($controller) . 'Controller';
            }

            $rows[] = [$name, $route->pattern(), $controller, $defaults['action'] ?? '', $defaults['directory'] ?? ''];
        }

        if (count($rows) === 1) {
            Cli::write('No routes are registered.');

            return;
        }

        $widths = [];
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string) $cell));
            }
        }

        foreach ($rows as $n => $row) {
            $line = rtrim(implode('  ', array_map(fn ($cell, $i) => str_pad((string) $cell, $widths[$i] + strlen((string) $cell) - mb_strlen((string) $cell)), $row, array_keys($row))));
            Cli::write($n === 0 ? Cli::color($line, 'yellow') : $line);
        }
    }
}
