# Console

Kohana's console (the Minion module) runs commands from the terminal through the `kohana` script in the project root:

    php kohana                          # list commands with descriptions
    php kohana make:controller Post     # run a command with an argument
    php kohana key:generate --show      # run a command with an option
    php kohana route:list --help        # show a command's help

The console loads the Minion module by itself, so you only need to enable it in `bootstrap/app.php` when you also run commands through `public/index.php`.

## Built-in commands

| Command | Does |
|---|---|
| `help`, `-h` | Lists all commands. |
| `version`, `-V`, `--version` | Shows the Kohana and PHP versions. |
| `key:generate [--show] [--force]` | Writes a random `APP_KEY` and `COOKIE_SALT` to `.env`. See [Environment Configuration](../kohana/environment). |
| `make:controller Name [--force]` | Creates `App\Http\Controllers\NameController`. `Admin/User` creates `App\Http\Controllers\Admin\UserController`. |
| `make:model Name [--table=name] [--force]` | Creates an ORM model `App\Models\Name`. |
| `make:command name [--force]` | Creates a console command; `db:seed` creates `App\Console\Commands\Db\SeedCommand`. |
| `route:list` | Lists the routes in matching order with their default controller and action. |
| `cache:clear [--store=group]` | Empties `Application::$cacheDir`, and with `--store` also a cache module store. |
| `serve [--host=127.0.0.1] [--port=8000]` | Serves the application with PHP's built-in web server, for development. |
| `make:migration name [--create=table] [--table=table]` | Creates a migration in `database/migrations`. See [Migrations](../database/migrations). |
| `make:seeder Name [--force]` | Creates `Database\Seeders\NameSeeder`. See [Seeding](../database/seeding). |
| `migrate [--seed] [--database=group] [--force]` | Runs pending migrations. |
| `migrate:rollback [--step=N] [--all] [--database=group] [--force]` | Reverts the last batch, the last N migrations, or all. |
| `migrate:status [--database=group]` | Shows which migrations ran. |
| `db:seed [--class=Name] [--database=group] [--force]` | Runs a seeder (default `DatabaseSeeder`). |
| `auth:migration [--force]` | Copies the ORM auth tables migration into `database/migrations` (ORM module). See [Auth](../auth/index). |
| `session:migration [--force]` | Copies the sessions table migration into `database/migrations` (database module). See [Sessions](../kohana/sessions). |
