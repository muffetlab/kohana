<?php

use Kohana\Auth\Auth;
use Kohana\Auth\Models\User;
use Kohana\Auth\OrmAuth;
use Kohana\Database\Connection;
use Kohana\Database\DB;
use Kohana\Database\PgsqlConnection;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/AuthTest.php';

/**
 * Login through the ORM driver, including the upgrade of Kohana 3 hashes.
 */
#[Group('kohana')]
#[Group('kohana.auth')]
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class Kohana_OrmAuthTest extends Kohana\Testing\TestCase
{
    use CreatesAuth;

    protected static $connection = 'default';

    private string $previousDefault;

    private ?Auth $previousInstance;

    private OrmAuth $orm;

    public function setUp(): void
    {
        parent::setUp();

        try {
            $db = Connection::instance(static::$connection);
            $db->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }

        $this->previousDefault = Connection::$default;
        Connection::$default = static::$connection;

        $id = $db instanceof PgsqlConnection ? 'id SERIAL PRIMARY KEY' : 'id INT AUTO_INCREMENT PRIMARY KEY';

        foreach (['user_tokens', 'roles_users', 'users', 'roles'] as $table) {
            $db->query(Connection::UPDATE, "DROP TABLE IF EXISTS $table");
        }
        $db->query(Connection::UPDATE, "CREATE TABLE roles ($id, name VARCHAR(32) NOT NULL, description VARCHAR(255) NOT NULL)");
        $db->query(Connection::UPDATE, 'CREATE TABLE roles_users (user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY (user_id, role_id))');
        $db->query(Connection::UPDATE, "CREATE TABLE users ($id, email VARCHAR(254) NOT NULL, username VARCHAR(32) NOT NULL, password VARCHAR(255) NOT NULL, logins INT NOT NULL DEFAULT 0, last_login INT NULL)");
        $db->query(Connection::UPDATE, "CREATE TABLE user_tokens ($id, user_id INT NOT NULL, user_agent VARCHAR(40) NOT NULL, token VARCHAR(40) NOT NULL, created INT NOT NULL, expires INT NOT NULL)");

        DB::insert('roles', ['name', 'description'])->values(['login', 'Login'])->execute($db);

        // The User model's password filter calls Auth::instance()->hash()
        $this->orm = $this->auth(OrmAuth::class, ['hash_key' => 'old-key', 'lifetime' => 60]);
        $property = new ReflectionProperty(Auth::class, 'instance');
        $this->previousInstance = $property->getValue();
        $property->setValue(null, $this->orm);
    }

    public function tearDown(): void
    {
        if (isset($this->previousDefault)) {
            Connection::$default = $this->previousDefault;
            (new ReflectionProperty(Auth::class, 'instance'))->setValue(null, $this->previousInstance);
        }

        parent::tearDown();
    }

    protected function createUser(string $username, string $storedHash): void
    {
        [$id] = DB::insert('users', ['email', 'username', 'password'])->values(["$username@example.com", $username, $storedHash])->execute();
        DB::insert('roles_users', ['user_id', 'role_id'])->values([$id, 1])->execute();
    }

    protected function storedHash(string $username): string
    {
        return DB::select('password')->from('users')->where('username', '=', $username)->execute()->get('password');
    }

    public function test_login_with_current_hash()
    {
        $this->createUser('ayse', $this->orm->hash('secret'));

        $this->assertFalse($this->orm->login('ayse', 'wrong'));
        $this->assertFalse($this->orm->login('nobody', 'secret'));
        $this->assertTrue($this->orm->login('ayse', 'secret'));
        $this->assertSame('ayse', $this->orm->getUser()->username);
        $this->assertSame(get_class(Connection::instance(static::$connection)), get_class((fn () => $this->db)->call($this->orm->getUser())));
        $this->assertTrue($this->orm->checkPassword('secret'));
    }

    public function test_legacy_hash_is_upgraded_on_login()
    {
        $this->createUser('ali', hash_hmac('sha256', 'secret', 'old-key'));

        $this->assertFalse($this->orm->login('ali', 'wrong'));
        $this->assertSame(hash_hmac('sha256', 'secret', 'old-key'), $this->storedHash('ali'), 'A failed login must not rehash');

        $this->assertTrue($this->orm->login('ali', 'secret'));

        $hash = $this->storedHash('ali');
        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(password_verify('secret', $hash));

        // The upgraded hash keeps working
        $this->orm->logout();
        $this->assertTrue($this->orm->login('ali', 'secret'));
    }

    public function test_user_model_hashes_password_on_save()
    {
        $user = new User();
        $user->values(['username' => 'veli', 'email' => 'veli@example.com', 'password' => 'secret'], ['username', 'email', 'password'])->create();

        $this->assertStringStartsWith('$argon2id$', $this->storedHash('veli'));
    }
}
