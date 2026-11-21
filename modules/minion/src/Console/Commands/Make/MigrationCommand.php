<?php

namespace Kohana\Console\Commands\Make;

use Kohana\Console\Cli;
use Kohana\Console\Command;

/**
 * Creates a migration file in database/migrations.
 *
 *     php kohana make:migration create_posts_table          # Schema::create('posts', ...)
 *     php kohana make:migration add_slug_to_posts_table     # Schema::table('posts', ...)
 *     php kohana make:migration change_things --table=posts
 *
 * Options:
 *   --create  Table to create
 *   --table   Table to change
 */
class MigrationCommand extends Command
{
    protected $options = [
        'create' => null,
        'table' => null,
    ];

    protected function handle(array $params)
    {
        $name = $this->argument(0);

        if ($name === null || !preg_match('/^[a-z][a-z0-9_]*$/', $name = strtolower($name))) {
            Cli::write('Give a snake_case name, for example: php kohana make:migration create_posts_table');

            return;
        }

        $file = static::path($name);

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, static::stub($name, $params['create'], $params['table']));

        Cli::write(Cli::color('Created migration ' . basename($file, '.php'), 'green') . ' in ' . str_replace(BASEPATH, '', $file));
    }

    /**
     * @return string File for a new migration, prefixed with the current date and time
     */
    public static function path(string $name, ?int $time = null): string
    {
        $dir = database_path('migrations') . DIRECTORY_SEPARATOR;
        $time ??= time();

        // Migrations run in file name order; never reuse or go back from an existing timestamp
        foreach (glob($dir . '*.php') ?: [] as $existing) {
            if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})_/', basename($existing), $m)) {
                $time = max($time, mktime((int) $m[4], (int) $m[5], (int) $m[6], (int) $m[2], (int) $m[3], (int) $m[1]) + 1);
            }
        }

        return $dir . date('Y_m_d_His', $time) . '_' . $name . '.php';
    }

    /**
     * Builds the migration source. Without --create or --table the table is
     * guessed from names like create_posts_table and add_slug_to_posts_table.
     */
    public static function stub(string $name, ?string $create = null, ?string $table = null): string
    {
        if ($create === null && $table === null) {
            if (preg_match('/^create_(\w+?)_table$/', $name, $match)) {
                $create = $match[1];
            } elseif (preg_match('/_(?:to|from|in)_(\w+?)_table$/', $name, $match)) {
                $table = $match[1];
            }
        }

        if ($create !== null) {
            $up = "        Schema::create('$create', function (Blueprint \$table) {\n            \$table->id();\n            \$table->timestamps();\n        });";
            $down = "        Schema::dropIfExists('$create');";
        } elseif ($table !== null) {
            $up = "        Schema::table('$table', function (Blueprint \$table) {\n            //\n        });";
            $down = "        Schema::table('$table', function (Blueprint \$table) {\n            //\n        });";
        } else {
            $up = '        //';
            $down = '        //';
        }

        return <<<PHP
            <?php

            use Kohana\\Database\\Migrations\\Migration;
            use Kohana\\Database\\Schema\\Blueprint;
            use Kohana\\Database\\Schema\\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
            $up
                }

                public function down(): void
                {
            $down
                }
            };

            PHP;
    }
}
