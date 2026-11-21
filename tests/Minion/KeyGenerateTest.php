<?php

use Kohana\Console\Commands\Key\GenerateCommand;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('kohana')]
#[Group('kohana.minion')]
class Kohana_KeyGenerateTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/kohana-key-' . uniqid();
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
        foreach (['.env', '.env.example'] as $file) {
            if (is_file("$this->dir/$file")) {
                unlink("$this->dir/$file");
            }
        }
        rmdir($this->dir);

        parent::tearDown();
    }

    public function test_creates_env_from_example_and_fills_empty_values()
    {
        file_put_contents("$this->dir/.env.example", "APP_ENV=development\nAPP_KEY=\nDB_HOST=127.0.0.1\n");

        $result = GenerateCommand::write("$this->dir/.env", ['APP_KEY' => 'base64:abc+/=', 'COOKIE_SALT' => 'salt$1']);

        $this->assertSame(['APP_KEY' => true, 'COOKIE_SALT' => true], $result);
        $this->assertSame("APP_ENV=development\nAPP_KEY=base64:abc+/=\nDB_HOST=127.0.0.1\nCOOKIE_SALT=salt\$1\n", file_get_contents("$this->dir/.env"));
    }

    public function test_keeps_existing_values_unless_forced()
    {
        file_put_contents("$this->dir/.env", "APP_KEY=old\n");

        $this->assertSame(['APP_KEY' => false], GenerateCommand::write("$this->dir/.env", ['APP_KEY' => 'new']));
        $this->assertSame("APP_KEY=old\n", file_get_contents("$this->dir/.env"));

        $this->assertSame(['APP_KEY' => true], GenerateCommand::write("$this->dir/.env", ['APP_KEY' => 'new'], true));
        $this->assertSame("APP_KEY=new\n", file_get_contents("$this->dir/.env"));
    }

    public function test_generated_values_are_random_and_usable()
    {
        file_put_contents("$this->dir/.env", '');

        GenerateCommand::write("$this->dir/.env", ['APP_KEY' => 'base64:' . base64_encode(random_bytes(32))]);

        $this->assertMatchesRegularExpression('/^APP_KEY=base64:[A-Za-z0-9+\/]{43}=$/m', file_get_contents("$this->dir/.env"));
    }
}
