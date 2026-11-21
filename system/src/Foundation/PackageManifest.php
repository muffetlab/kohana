<?php

namespace Kohana\Foundation;

/**
 * Discovers Kohana modules installed with Composer.
 *
 * A package declares itself in its composer.json:
 *
 *     "extra": {
 *         "kohana": {
 *             "module": "blog",
 *             "path": ".",
 *             "commands": {
 *                 "blog:import": "Acme\\Blog\\Console\\ImportCommand"
 *             }
 *         }
 *     }
 *
 * - module: name of the module (required); its directory joins the cascading
 *   filesystem, so its config/, views/, messages/, i18n/ and migrations/ work
 * - path: module directory relative to the package (default: the package root)
 * - commands: console commands, name => class
 *
 * The application can skip packages in its own composer.json:
 *
 *     "extra": { "kohana": { "dont-discover": ["acme/blog"] } }    // or ["*"]
 *
 * The result is cached in bootstrap/cache/packages.php and rebuilt whenever
 * vendor/composer/installed.json changes, or with `php kohana package:discover`.
 *
 * @package    Kohana
 * @category   Base
 */
class PackageManifest
{
    protected ?array $manifest = null;

    /**
     * @param string $basePath Project root (where composer.json is)
     * @param string $vendorPath Composer's vendor directory
     * @param string $manifestPath Cache file
     */
    public function __construct(
        protected string $basePath,
        protected string $vendorPath,
        protected string $manifestPath,
    ) {
    }

    /**
     * @return array Module name => absolute path
     */
    public function modules(): array
    {
        return $this->manifest()['modules'];
    }

    /**
     * @return array Command name => class
     */
    public function commands(): array
    {
        return $this->manifest()['commands'];
    }

    /**
     * @return array Composer package name => module name
     */
    public function packages(): array
    {
        return $this->manifest()['packages'];
    }

    /**
     * Returns the manifest, rebuilding the cache when Composer's list of
     * installed packages is newer.
     */
    public function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $installed = $this->installedFile();

        if (is_file($this->manifestPath) && (!is_file($installed) || filemtime($this->manifestPath) >= filemtime($installed))) {
            $manifest = require $this->manifestPath;

            if (is_array($manifest) && isset($manifest['modules'], $manifest['commands'], $manifest['packages'])) {
                return $this->manifest = $manifest;
            }
        }

        return $this->build();
    }

    /**
     * Reads the installed packages and writes the cache file.
     */
    public function build(): array
    {
        $manifest = ['modules' => [], 'commands' => [], 'packages' => []];
        $ignore = $this->dontDiscover();
        $installed = $this->installedFile();

        $packages = is_file($installed) ? json_decode((string) file_get_contents($installed), true) : [];
        // Composer 2 wraps the list in "packages"; Composer 1 did not
        $packages = $packages['packages'] ?? $packages ?? [];

        foreach ($packages as $package) {
            $extra = $package['extra']['kohana'] ?? null;

            if (!is_array($extra) || empty($extra['module']) || in_array('*', $ignore, true) || in_array($package['name'], $ignore, true)) {
                continue;
            }

            $root = isset($package['install-path'])
                ? $this->vendorPath . 'composer' . DIRECTORY_SEPARATOR . $package['install-path']
                : $this->vendorPath . $package['name'];
            $path = realpath($root . DIRECTORY_SEPARATOR . ($extra['path'] ?? '.'));

            if ($path === false) {
                continue;
            }

            $manifest['modules'][$extra['module']] = $path . DIRECTORY_SEPARATOR;
            $manifest['packages'][$package['name']] = $extra['module'];

            foreach ((array) ($extra['commands'] ?? []) as $name => $class) {
                $manifest['commands'][$name] = $class;
            }
        }

        ksort($manifest['modules']);
        ksort($manifest['commands']);

        $this->write($manifest);

        return $this->manifest = $manifest;
    }

    protected function installedFile(): string
    {
        return $this->vendorPath . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
    }

    /**
     * @return string[] Packages the application excludes in its composer.json
     */
    protected function dontDiscover(): array
    {
        $file = $this->basePath . 'composer.json';
        $composer = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];

        return (array) ($composer['extra']['kohana']['dont-discover'] ?? []);
    }

    protected function write(array $manifest): void
    {
        $dir = dirname($this->manifestPath);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return;
        }

        // A failed write only costs a rebuild on the next request
        @file_put_contents($this->manifestPath, '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($manifest, true) . ';' . PHP_EOL, LOCK_EX);
    }
}
