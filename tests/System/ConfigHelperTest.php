<?php

use Kohana\Cache\Cache;
use Kohana\Foundation\Application;
use Kohana\Session\Session;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('kohana')]
#[Group('kohana.core.config')]
class Kohana_ConfigHelperTest extends TestCase
{
    public function test_config_reads_dot_paths()
    {
        $this->assertSame(Application::$config->load('app'), config('app'));
        $this->assertSame('utf-8', config('app.charset'));
        $this->assertSame('pgsql', config('database.pgsql.type'));
        $this->assertSame('fallback', config('database.missing.type', 'fallback'));
        $this->assertNull(config('nonexistent_file.key'));
    }

    public function test_settings_come_from_the_project_config_directory()
    {
        foreach (['app', 'auth', 'cache', 'cookie', 'curl', 'database', 'encrypt', 'image', 'session', 'url'] as $file) {
            // Only the project defines them; modules and the system ship no copies
            $this->assertSame([BASEPATH . 'config' . DIRECTORY_SEPARATOR . "$file.php"], Application::findFile('config', $file), $file);
        }
    }

    public function test_default_cache_store_and_session_driver_come_from_config()
    {
        $this->setEnvironment(['Kohana\Cache\Cache::$default' => null, 'Kohana\Session\Session::$default' => null]);

        $cache = Application::$config->load('cache');
        $cache->set('default', 'file');
        $this->assertSame(Cache::instance('file'), Cache::instance());

        $this->assertSame('native', config('session.driver'));
        $this->assertNull(Session::$default);
    }
}
