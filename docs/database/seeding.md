# Seeding

Seeders fill the database with initial or test data. They live in `app/Database/Seeders` under `Database\Seeders`. The application ships an empty `DatabaseSeeder`; create more with:

    php kohana make:seeder User

A seeder implements `run()` and can call other seeders:

    <?php

    namespace Database\Seeders;

    use Kohana\Database\DB;
    use Kohana\Database\Seeder;

    class DatabaseSeeder extends Seeder
    {
        public function run(): void
        {
            DB::insert('roles', ['name', 'description'])
                ->values(['login', 'Login privileges'])
                ->values(['admin', 'Administrative user'])
                ->execute();

            $this->call([UserSeeder::class, PostSeeder::class]);
        }
    }

ORM models work too:

    Model::factory('User')->values(['username' => 'admin', 'email' => 'admin@example.com', 'password' => 'secret'], ['username', 'email', 'password'])->create();

## Running seeders

    php kohana db:seed                         # DatabaseSeeder
    php kohana db:seed --class=UserSeeder      # one seeder
    php kohana db:seed --database=pgsql        # on another connection
    php kohana migrate --seed                  # migrate, then DatabaseSeeder

While a seeder runs, `Connection::$default` points to the chosen connection, so `DB::...->execute()` and ORM models use it without passing it around. In production, `db:seed` only runs with `--force`.
