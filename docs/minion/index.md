# Console

Kohana's console (the Minion module) runs commands from the terminal through the `kohana` script in the project root:

    php kohana                          # list commands with descriptions
    php kohana make:controller Post     # run a command with an argument
    php kohana key:generate --show      # run a command with an option
    php kohana route:list --help        # show a command's help

The console loads the Minion module by itself, so you only need to enable it in `app/bootstrap.php` when you also run commands through `public/index.php`.

## Built-in commands

| Command | Does |
|---|---|
| `help` | Lists all commands. |
| `key:generate [--show] [--force]` | Writes a random `APP_KEY` and `COOKIE_SALT` to `.env`. See [Environment Configuration](../kohana/environment). |
| `make:controller Name [--force]` | Creates `App\Http\Controllers\NameController`. `Admin/User` creates `App\Http\Controllers\Admin\UserController`. |
| `make:model Name [--table=name] [--force]` | Creates an ORM model `App\Models\Name`. |
| `make:command name [--force]` | Creates a console command; `db:seed` creates `App\Console\Commands\Db\SeedCommand`. |
| `route:list` | Lists the routes in matching order with their default controller and action. |
| `cache:clear [--store=group]` | Empties `Application::$cacheDir`, and with `--store` also a cache module store. |
| `serve [--host=127.0.0.1] [--port=8000]` | Serves the application with PHP's built-in web server, for development. |
