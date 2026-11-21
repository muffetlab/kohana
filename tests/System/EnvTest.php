<?php

use Kohana\Encryption\OpensslEncrypter;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('kohana')]
#[Group('kohana.core.env')]
class Kohana_EnvTest extends TestCase
{
    public function tearDown(): void
    {
        unset($_ENV['KOHANA_TEST_VAR'], $_SERVER['KOHANA_TEST_VAR']);
        putenv('KOHANA_TEST_VAR');

        parent::tearDown();
    }

    public static function provider_casts(): array
    {
        return [
            ['true', true],
            ['(true)', true],
            ['TRUE', true],
            ['false', false],
            ['(false)', false],
            ['null', null],
            ['(null)', null],
            ['empty', ''],
            ['"quoted value"', 'quoted value'],
            ["'single'", 'single'],
            ['plain', 'plain'],
            ['0', '0'],
            ['"', '"'],
        ];
    }

    #[DataProvider('provider_casts')]
    public function test_env_casts_values(string $raw, $expected)
    {
        $_ENV['KOHANA_TEST_VAR'] = $raw;

        $this->assertSame($expected, env('KOHANA_TEST_VAR'));
    }

    public function test_env_reads_env_server_and_getenv_in_order()
    {
        $this->assertSame('default', env('KOHANA_TEST_VAR', 'default'));
        $this->assertSame('lazy', env('KOHANA_TEST_VAR', fn () => 'lazy'));

        putenv('KOHANA_TEST_VAR=from-getenv');
        $this->assertSame('from-getenv', env('KOHANA_TEST_VAR'));

        $_SERVER['KOHANA_TEST_VAR'] = 'from-server';
        $this->assertSame('from-server', env('KOHANA_TEST_VAR'));

        $_ENV['KOHANA_TEST_VAR'] = 'from-env';
        $this->assertSame('from-env', env('KOHANA_TEST_VAR'));
    }

    public function test_dotenv_file_is_loaded_without_overriding_real_variables()
    {
        $dir = sys_get_temp_dir() . '/kohana-env-' . uniqid();
        mkdir($dir);
        file_put_contents("$dir/.env", "KOHANA_TEST_VAR=from-file\n");

        try {
            Dotenv\Dotenv::createImmutable($dir)->safeLoad();
            $this->assertSame('from-file', env('KOHANA_TEST_VAR'));

            $_ENV['KOHANA_TEST_VAR'] = $_SERVER['KOHANA_TEST_VAR'] = 'real';
            Dotenv\Dotenv::createImmutable($dir)->safeLoad();
            $this->assertSame('real', env('KOHANA_TEST_VAR'));
        } finally {
            unlink("$dir/.env");
            rmdir($dir);
        }
    }

    public function test_base64_app_key_is_decoded()
    {
        $raw = random_bytes(32);
        $plain = new OpensslEncrypter('test', ['key' => $raw, 'method' => 'AES-256-CTR']);
        $prefixed = new OpensslEncrypter('test', ['key' => 'base64:' . base64_encode($raw), 'method' => 'AES-256-CTR']);

        $this->assertSame('secret', $prefixed->decode($plain->encode('secret')));
    }
}
