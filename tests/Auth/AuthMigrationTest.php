<?php

use Kohana\Auth\Auth;
use Kohana\Auth\Models\Role;
use Kohana\Auth\Models\User;
use Kohana\Auth\OrmAuth;
use Kohana\Console\Commands\Auth\MigrationCommand;
use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Database\Migrations\Migrator;
use Kohana\Database\Schema\Schema;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/AuthTest.php';

/**
 * The auth:migration template creates working ORM auth tables.
 */
#[Group('kohana')]
#[Group('kohana.auth')]
#[Group('kohana.database.migrations')]
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class Kohana_AuthMigrationTest extends TestCase
{
    use CreatesAuth;

    public static function provider_connections(): array
    {
        return [['mysqli'], ['pgsql']];
    }

    #[DataProvider('provider_connections')]
    public function test_auth_tables_support_login(string $name)
    {
        try {
            Connection::instance($name)->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        foreach (['user_tokens', 'roles_users', 'users', 'roles', 'migrations'] as $table) {
            Schema::connection($name)->dropIfExists($table);
        }

        $migrator = new Migrator($name, 'migrations', ['2026_01_01_000000_create_auth_tables' => MigrationCommand::stub()]);
        $migrator->migrate();

        $previousDefault = Connection::$default;
        $instance = new ReflectionProperty(Auth::class, 'instance');
        $previousInstance = $instance->getValue();
        Connection::$default = $name;

        try {
            $this->assertSame(['admin', 'login'], DB::select('name')->from('roles')->orderBy('name')->execute($name)->asArray(null, 'name'));

            $this->setEnvironment(['Kohana\Cookie\Cookie::$salt' => 'test-salt']);
            $auth = $this->auth(OrmAuth::class, ['lifetime' => 60]);
            $instance->setValue(null, $auth);

            $user = new User();
            $user->values(['username' => 'ayse', 'email' => 'ayse@example.com', 'password' => 'secret'], ['username', 'email', 'password'])->create();
            $user->add('roles', new Role(['name' => 'login']));

            $this->assertTrue($auth->login('ayse', 'secret', true), 'Login with remember me creates a user token');
            $this->assertSame(1, (int) DB::select([DB::expr('COUNT(*)'), 'c'])->from('user_tokens')->execute($name)->get('c'));

            // Deleting the user cascades to roles_users and user_tokens
            $user->delete();
            $this->assertSame(0, (int) DB::select([DB::expr('COUNT(*)'), 'c'])->from('roles_users')->execute($name)->get('c'));
            $this->assertSame(0, (int) DB::select([DB::expr('COUNT(*)'), 'c'])->from('user_tokens')->execute($name)->get('c'));
        } finally {
            Connection::$default = $previousDefault;
            $instance->setValue(null, $previousInstance);
            $migrator->rollback(0, true);
        }

        $this->assertFalse(Schema::connection($name)->hasTable('users'));
    }
}
