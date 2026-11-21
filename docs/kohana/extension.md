# Extending Classes

Kohana 3 let you replace a core class by putting a file with the same name in `app/classes` (transparent extension). Kohana 4 classes live in the `Kohana\` namespace and are loaded by Composer, so that mechanism is gone. Extend a class the way you would in any modern PHP framework: subclass it in your own namespace and use your subclass.

[!!] You should **never** modify the files that are distributed with Kohana. Put your changes in your own classes so that upgrades stay painless.

## Adding methods to a class

Create a subclass under `App\`, which Composer maps to `app/classes/`. For example, to add encrypted cookies, create `app/classes/Cookie/EncryptedCookie.php`:

    <?php

    namespace App\Cookie;

    use Kohana\Cookie\Cookie;
    use Kohana\Encryption\Encrypter;

    class EncryptedCookie extends Cookie
    {
        /**
         * @var string Encryption instance name
         */
        public static $encryption = 'default';

        public static function encrypt(string $name, string $value, ?int $lifetime = null): bool
        {
            $value = Encrypter::instance(static::$encryption)->encode($value);

            return parent::set($name, $value, $lifetime);
        }

        public static function decrypt(string $name, $default = null)
        {
            $value = parent::get($name);

            return $value === null ? $default : Encrypter::instance(static::$encryption)->decode($value);
        }
    }

Now `EncryptedCookie::encrypt('secret', $data)` creates an encrypted cookie that `EncryptedCookie::decrypt('secret')` reads back.

## Replacing a driver

Classes that Kohana creates from configuration (cache stores, session handlers, database connections, auth, image and encryption drivers) accept a fully qualified class name instead of a driver name. Extend the driver and point the configuration at your class:

    // app/classes/Cache/FileStore.php
    namespace App\Cache;

    class FileStore extends \Kohana\Cache\FileStore
    {
        // ...
    }

    // app/config/cache.php
    return [
        'file' => [
            'driver' => App\Cache\FileStore::class,
            'cache_dir' => APPPATH . 'cache',
        ],
    ];

## Configuration, views and messages still cascade

Only classes stopped using the [cascading filesystem](files). Config files, views, messages and i18n files are still found in `application`, then in each module, then in `system`, so you can override `views/error.php` or merge into `config/session.php` exactly as before.
