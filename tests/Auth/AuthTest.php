<?php

use PHPUnit\Framework\Attributes\Group;
use Kohana\Auth\Auth;
use Kohana\Auth\FileAuth;
use Kohana\Session\Session;
use Kohana\Testing\TestCase;

trait CreatesAuth
{
    /**
     * Creates a driver with an in-memory session.
     */
    protected function auth(string $class, array $config = []): Auth
    {
        $session = $this->mockAbstract(Session::class, null);
        $session->method('regenerateSession')->willReturn('session-id');

        $auth = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        (fn () => [$this->config = $config + ['session_key' => 'auth_user', 'hash_method' => 'sha256', 'hash_key' => null, 'users' => []], $this->session = $session])->call($auth);

        if ($auth instanceof FileAuth) {
            (fn () => $this->users = $this->config['users'])->call($auth);
        }

        return $auth;
    }
}

/**
 * Password hashing and the File driver.
 */
#[Group('kohana')]
#[Group('kohana.auth')]
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class Kohana_AuthTest extends TestCase
{
    use CreatesAuth;

    public function test_hash_uses_argon2id_by_default()
    {
        $auth = $this->auth(FileAuth::class);
        $hash = $auth->hash('secret');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertNotSame($hash, $auth->hash('secret'), 'Each hash has its own salt');
        $this->assertTrue($auth->verify('secret', $hash));
        $this->assertFalse($auth->verify('wrong', $hash));
        $this->assertFalse($auth->needsRehash($hash));
    }

    public function test_hash_algorithm_and_options_are_configurable()
    {
        $auth = $this->auth(FileAuth::class, ['hash_algorithm' => PASSWORD_BCRYPT, 'hash_options' => ['cost' => 4]]);
        $hash = $auth->hash('secret');

        $this->assertStringStartsWith('$2y$04$', $hash);
        $this->assertTrue($auth->verify('secret', $hash));

        // A hash made with other settings should be upgraded
        $this->assertTrue($this->auth(FileAuth::class)->needsRehash($hash));
    }

    public function test_legacy_hmac_hashes_still_verify_and_need_rehash()
    {
        $legacy = hash_hmac('sha256', 'secret', 'old-key');
        $auth = $this->auth(FileAuth::class, ['hash_key' => 'old-key']);

        $this->assertTrue($auth->verify('secret', $legacy));
        $this->assertFalse($auth->verify('wrong', $legacy));
        $this->assertTrue($auth->needsRehash($legacy));

        // Without the old key a legacy hash can never match
        $this->assertFalse($this->auth(FileAuth::class)->verify('secret', $legacy));
    }

    public function test_empty_hash_never_verifies()
    {
        $auth = $this->auth(FileAuth::class);

        $this->assertFalse($auth->verify('', ''));
        $this->assertFalse($auth->verify('secret', null));
    }

    public function test_file_driver_login_and_check_password()
    {
        $auth = $this->auth(FileAuth::class, ['users' => ['admin' => password_hash('secret', PASSWORD_BCRYPT, ['cost' => 4])]]);

        $this->assertFalse($auth->login('admin', 'wrong'));
        $this->assertFalse($auth->login('nobody', 'secret'));
        $this->assertFalse($auth->checkPassword('secret'), 'Nobody is logged in yet');

        $this->assertTrue($auth->login('admin', 'secret'));
        $this->assertSame('admin', $auth->getUser());
        $this->assertTrue($auth->checkPassword('secret'));
        $this->assertFalse($auth->checkPassword('wrong'));
    }
}
