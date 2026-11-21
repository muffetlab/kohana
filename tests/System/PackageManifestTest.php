<?php

use Kohana\Foundation\PackageManifest;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('kohana')]
#[Group('kohana.core.packages')]
class Kohana_PackageManifestTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/kohana-packages-' . uniqid() . '/';
        mkdir($this->dir . 'vendor/composer', 0777, true);
        mkdir($this->dir . 'vendor/acme/blog/module', 0777, true);
        mkdir($this->dir . 'vendor/acme/shop', 0777, true);
        mkdir($this->dir . 'vendor/other/lib', 0777, true);

        $this->installed([
            ['name' => 'acme/blog', 'install-path' => '../acme/blog', 'extra' => ['kohana' => ['module' => 'blog', 'path' => 'module', 'commands' => ['blog:import' => 'Acme\\Blog\\ImportCommand']]]],
            ['name' => 'acme/shop', 'install-path' => '../acme/shop', 'extra' => ['kohana' => ['module' => 'shop']]],
            ['name' => 'other/lib', 'install-path' => '../other/lib', 'extra' => ['laravel' => []]],
        ]);
    }

    public function tearDown(): void
    {
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->dir);

        parent::tearDown();
    }

    private function installed(array $packages): void
    {
        file_put_contents($this->dir . 'vendor/composer/installed.json', json_encode(['packages' => $packages, 'dev' => true]));
    }

    private function manifest(): PackageManifest
    {
        return new PackageManifest($this->dir, $this->dir . 'vendor/', $this->dir . 'bootstrap/cache/packages.php');
    }

    public function test_discovers_modules_and_commands()
    {
        $manifest = $this->manifest();

        $this->assertSame([
            'blog' => realpath($this->dir . 'vendor/acme/blog/module') . DIRECTORY_SEPARATOR,
            'shop' => realpath($this->dir . 'vendor/acme/shop') . DIRECTORY_SEPARATOR,
        ], $manifest->modules());
        $this->assertSame(['blog:import' => 'Acme\\Blog\\ImportCommand'], $manifest->commands());
        $this->assertSame(['acme/blog' => 'blog', 'acme/shop' => 'shop'], $manifest->packages());
        $this->assertFileExists($this->dir . 'bootstrap/cache/packages.php');
    }

    public function test_uses_cache_until_installed_packages_change()
    {
        $this->manifest()->modules();

        // Same mtime: the cache is used even though installed.json says otherwise now
        $this->installed([]);
        touch($this->dir . 'vendor/composer/installed.json', time() - 10);
        $this->assertArrayHasKey('blog', $this->manifest()->modules());

        // A newer installed.json rebuilds it
        touch($this->dir . 'vendor/composer/installed.json', time() + 10);
        $this->assertSame([], $this->manifest()->modules());
    }

    public function test_dont_discover()
    {
        file_put_contents($this->dir . 'composer.json', json_encode(['extra' => ['kohana' => ['dont-discover' => ['acme/shop']]]]));
        $this->assertSame(['blog'], array_keys($this->manifest()->build()['modules']));

        file_put_contents($this->dir . 'composer.json', json_encode(['extra' => ['kohana' => ['dont-discover' => ['*']]]]));
        $this->assertSame([], $this->manifest()->build()['modules']);
    }

    public function test_works_without_composer_files()
    {
        unlink($this->dir . 'vendor/composer/installed.json');

        $this->assertSame([], $this->manifest()->modules());
    }
}
