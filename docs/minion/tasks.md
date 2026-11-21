# Writing Commands

Generate a command with `make:command`, or create the class yourself. Commands live in `app/classes/Console/Commands` under `App\Console\Commands`, extend `Kohana\Console\Command` and implement `handle()`:

    php kohana make:command report

    <?php

    namespace App\Console\Commands;

    use Kohana\Console\Cli;
    use Kohana\Console\Command;

    /**
     * Sends the daily report.
     *
     * Options:
     *   --to    Recipient address
     *   --dry   Print the report instead of sending it
     */
    class ReportCommand extends Command
    {
        protected $options = [
            'to' => 'team@example.com',
            'dry' => false,
        ];

        protected function handle(array $params)
        {
            $date = $this->argument(0, date('Y-m-d'));

            Cli::write("Report for $date to {$params['to']}");

            if ($params['dry'] !== false) {
                return;
            }

            // ...
        }
    }

    php kohana report 2026-11-21 --to=boss@example.com --dry

## Names

The command name comes from the class name: `ReportCommand` is `report`, and subdirectories add a prefix with a colon, so `App\Console\Commands\Db\SeedCommand` (in `Console/Commands/Db/SeedCommand.php`) is `db:seed`. Application commands win over Kohana's commands of the same name.

## Arguments and options

- **Arguments** are the values after the command name without dashes. Read them with `$this->argument(0)`, `$this->argument(1, $default)` or `$this->arguments()`.
- **Options** are `--name=value` or `--flag`. `$params` holds every accepted option with its default from `$options`. A flag given without a value is `null`, so test flags with `$params['dry'] !== false`.
- Options that are not listed in `$options` are rejected with an error.

## Validation

Add Validation rules for options by overriding `buildValidation()`:

    use Kohana\Validation\Validator;

    public function buildValidation(Validator $validation): Validator
    {
        return parent::buildValidation($validation)
            ->rule('to', 'not_empty')
            ->rule('to', 'email');
    }

The rules run for every call except `--help`.

## Help

`php kohana` lists commands with the first line of each class docblock, and `php kohana report --help` shows the whole docblock. Describe the arguments and options there.

## Output and input

`Kohana\Console\Cli` has helpers for terminal I/O:

| Method | Does |
|---|---|
| `Cli::write($text)` | Writes a line (or an array of lines). |
| `Cli::writeReplace($text, $endLine)` | Overwrites the current line, for progress output. |
| `Cli::color($text, 'green')` | Colors text. |
| `Cli::read('Continue?', ['y', 'n'])` | Asks for input, optionally from a list of choices. |
| `Cli::password('Password')` | Reads input without echoing it. |
| `Cli::wait(5, true)` | Waits, with an optional countdown. |
