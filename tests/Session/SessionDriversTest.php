<?php

use Kohana\Cache\Cache;
use Kohana\Console\Commands\Session\MigrationCommand;
use Kohana\Cookie\Cookie;
use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Database\Migrations\Migrator;
use Kohana\Database\Schema\Schema;
use Kohana\Foundation\Application;
use Kohana\Session\DatabaseSession;
use Kohana\Session\NativeSession;
use Kohana\Session\RedisSession;
use Kohana\Session\Session;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Lets a session driver write although output has started, and records the
 * session cookie instead of sending it.
 */
trait FakeSessionCookie
{
    public static array $cookies = [];

    protected function headersSent(): bool
    {
        return false;
    }

    protected function sendCookie(string $value): bool
    {
        static::$cookies[$this->name] = $value;

        return true;
    }

    protected function deleteCookie(): bool
    {
        unset(static::$cookies[$this->name]);

        return true;
    }
}

/**
 * Session drivers against real storage. The test runner has already printed
 * output, so the drivers run with headers treated as unsent and cookies recorded.
 */
#[Group('kohana')]
#[Group('kohana.session')]
class Kohana_SessionDriversTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Cookie::$salt = 'session-test-salt';
    }

    /**
     * Runs the scenario every driver must pass.
     *
     * @param callable(?string): Session $make Creates a session, optionally for an id
     * @param callable(string): bool $exists Whether data for an id is stored
     */
    protected function assertDriverWorks(callable $make, callable $exists): void
    {
        // A new session gets a random id and stores what was set
        $session = $make(null);
        $first = $session->id();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $first);

        $session->set('user_id', 42)->set('name', 'Ayşe');
        $this->assertTrue($session->write());
        $this->assertTrue($exists($first));

        // The next request reads it back
        $session = $make($first);
        $this->assertSame($first, $session->id());
        $this->assertSame(42, $session->get('user_id'));
        $this->assertSame('Ayşe', $session->get('name'));

        // Regenerating keeps the data under a new id; the old id stops working
        $second = $session->regenerate();
        $this->assertNotSame($first, $second);
        $this->assertTrue($session->write());
        $this->assertTrue($exists($second));
        $this->assertSame(42, $make($second)->get('user_id'));

        // An unknown id starts an empty session with a fresh id
        $unknown = $make(str_repeat('a', 40));
        $this->assertNull($unknown->get('user_id'));
        $this->assertNotSame(str_repeat('a', 40), $unknown->id());

        // Destroying removes the data
        $session = $make($second);
        $this->assertTrue($session->destroy());
        $this->assertFalse($exists($second));
    }

    public static function provider_connections(): array
    {
        return [['mysqli'], ['pgsql']];
    }

    #[DataProvider('provider_connections')]
    public function test_database_session(string $name)
    {
        try {
            Connection::instance($name)->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        Schema::connection($name)->dropIfExists('sessions');
        Schema::connection($name)->dropIfExists('migrations');
        $migrator = new Migrator($name, 'migrations', ['2026_01_01_000000_create_sessions_table' => MigrationCommand::stub()]);
        $migrator->migrate();

        try {
            $config = ['name' => 'test_session', 'group' => $name, 'gc' => 0] + Application::$config->load('session')->get('database');

            $this->assertDriverWorks(
                fn (?string $id) => new class ($config, $id) extends DatabaseSession {
                    use FakeSessionCookie;
                },
                fn (string $id) => DB::select('session_id')->from('sessions')->where('session_id', '=', $id)->execute($name)->count() === 1,
            );
        } finally {
            $migrator->rollback(0, true);
        }
    }

    public function test_redis_session()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis PHP Extension is not available');
        }

        Application::$config->load('cache')->set('session-test', [
            'driver' => 'redis',
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'database' => 15,
            'prefix' => 'kohana-test:',
        ]);

        try {
            $redis = Cache::instance('session-test')->redis();
        } catch (Kohana\Cache\CacheException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $config = ['name' => 'test_session', 'store' => 'session-test', 'prefix' => 'session:', 'ttl' => 60];

        $this->assertDriverWorks(
            fn (?string $id) => new class ($config, $id) extends RedisSession {
                use FakeSessionCookie;
            },
            fn (string $id) => (bool) $redis->exists('kohana-test:session:' . $id),
        );

        // Data expires with the configured time to live
        $session = new class ($config) extends RedisSession {
            use FakeSessionCookie;
        };
        $session->set('a', 1)->write();
        $ttl = $redis->ttl('kohana-test:session:' . $session->id());
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(60, $ttl);
    }

    public function test_native_session_uses_configured_save_path()
    {
        // An earlier test may have left a session open
        session_status() === PHP_SESSION_ACTIVE and session_write_close();

        $path = sys_get_temp_dir() . '/kohana-sessions-' . uniqid();

        try {
            $session = new NativeSession(['name' => 'test_session', 'save_path' => $path]);
            $session->set('user_id', 7);
            session_write_close();

            $this->assertSame($path, session_save_path());
            $this->assertFileExists($path . '/sess_' . $session->id());
        } finally {
            array_map('unlink', glob($path . '/sess_*') ?: []);
            is_dir($path) && rmdir($path);
        }
    }

    public function test_session_helper()
    {
        session_status() === PHP_SESSION_ACTIVE and session_write_close();

        $path = sys_get_temp_dir() . '/kohana-sessions-' . uniqid();
        Application::$config->load('session')->set('native', ['name' => 'test_session', 'save_path' => $path]);
        Session::$default = 'native';

        try {
            $this->assertInstanceOf(NativeSession::class, session());
            $this->assertSame(session(), session(['user_id' => 5, 'role' => 'admin']));
            $this->assertSame(5, session('user_id'));
            $this->assertSame('none', session('missing', 'none'));
        } finally {
            session_write_close();
            array_map('unlink', glob($path . '/sess_*') ?: []);
            is_dir($path) && rmdir($path);
        }
    }
}
