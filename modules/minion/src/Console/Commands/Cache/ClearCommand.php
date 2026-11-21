<?php

namespace Kohana\Console\Commands\Cache;

use Kohana\Cache\Cache;
use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Foundation\Application;

/**
 * Clears Kohana's internal cache directory and, optionally, a cache store.
 *
 *     php kohana cache:clear                  # Application::$cacheDir (file lists, routes, ...)
 *     php kohana cache:clear --store=redis    # also empty the "redis" store of the cache module
 *
 * Options:
 *   --store  Cache module group to empty with deleteAll()
 */
class ClearCommand extends Command
{
    protected $options = [
        'store' => null,
    ];

    protected function handle(array $params)
    {
        $removed = static::clearDirectory(Application::$cacheDir);
        Cli::write("Removed $removed cached files from " . Application::$cacheDir);

        if ($params['store'] !== null) {
            if (!class_exists(Cache::class) || !isset(Application::modules()['cache'])) {
                Cli::write(Cli::color('The cache module is not enabled', 'red'));

                return;
            }

            Cache::instance($params['store'])->deleteAll();
            Cli::write("Emptied cache store \"{$params['store']}\"");
        }
    }

    /**
     * Deletes the files and directories inside $dir, keeping $dir itself and
     * dot files such as .gitignore at its top level.
     *
     * @param string $dir Directory to empty
     * @return int Number of files removed
     */
    public static function clearDirectory(string $dir): int
    {
        $dir = realpath($dir);

        if ($dir === false || !is_dir($dir)) {
            return 0;
        }

        $removed = 0;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($items as $item) {
            if ($item->getPath() === $dir && str_starts_with($item->getFilename(), '.')) {
                continue;
            }

            if ($item->isDir() && !$item->isLink()) {
                @rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
                $removed++;
            }
        }

        return $removed;
    }
}
