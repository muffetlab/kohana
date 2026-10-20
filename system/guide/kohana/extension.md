# Extending Classes

Kohana's classes are distributed with the framework and its [modules](modules) and must never be edited directly: any change you make to them is lost as soon as you upgrade. Everything you need to change can be done without touching those files, either by extending a class in your application, or by overriding a class that the framework looks up at runtime.

[!!] You should **never** modify any of the files that are distributed with Kohana. Always extend or override the class instead, to prevent upgrade issues.

Class files are not part of the [cascading filesystem](files) in the way [config](files/config), [translation](files/i18n) and [message](files/messages) files are. A [class](autoloading) is loaded from the file that matches its namespace, so each class file is loaded from exactly one location.

## Extending a class

To add functionality to a class, extend it with your own class. For example, a [Cookie] subclass that can store its values encrypted with the [Encrypt] class:

    <?php

    // application/classes/Cookie.php

    namespace App;

    use Kohana\Cookie as BaseCookie;
    use Kohana\Encrypt;

    class Cookie extends BaseCookie
    {
        /**
         * @var  string  encryption instance to use
         */
        public static $encryption = 'default';

        /**
         * Sets an encrypted cookie.
         *
         * @uses  Encrypt::encode
         */
        public static function setEncrypted($name, $value, $lifetime = null)
        {
            $value = Encrypt::instance(static::$encryption)->encode((string) $value);

            return parent::set($name, $value, $lifetime);
        }

        /**
         * Gets an encrypted cookie.
         *
         * @uses  Encrypt::decode
         */
        public static function getEncrypted($name, $default = null)
        {
            if ($value = parent::get($name, null)) {
                $value = Encrypt::instance(static::$encryption)->decode($value);
            }

            return isset($value) ? $value : $default;
        }
    }

Calling `App\Cookie::setEncrypted('secret', $data)` stores an encrypted cookie, and `$data = App\Cookie::getEncrypted('secret')` returns it again.

Note that `App\Cookie` does **not** replace `Kohana\Cookie`. The framework refers to its own classes by their full name, so [Cookie::set] keeps using the original class. Use your own class wherever the extra functionality is needed.

## Overriding framework classes

Some classes are not referred to by a fixed name: the framework looks them up when it needs them, using [Kohana::find_class], which prefers the application namespace over the framework:

    // Resolves to App\Model\User if it exists, otherwise Kohana\Model\User
    $class = Kohana::find_class('Model\User');

Defining a class with the same name in the `App\` namespace therefore replaces the framework class everywhere it is used this way. For example, `application/classes/Model/User.php` replaces `Kohana\Model\User`:

    <?php

    // application/classes/Model/User.php

    namespace App\Model;

    use Kohana\Model\User as BaseUser;

    class User extends BaseUser
    {
        // Your application specific model
    }

These classes are resolved this way and can be overridden from your application:

 - [Controllers](mvc/controllers), e.g. `App\Controller\Welcome`
 - [Models](mvc/models), e.g. `App\Model\User`
 - Drivers of the [Auth], [Cache], [Database], [Encrypt], [Image] and [Session] classes, e.g. `App\Cache\File`
 - [Minion tasks](minion/tasks), e.g. `App\Task\Db\Migrate`

## Changing settings

Many classes are configured with static properties. Set them before the class is used, usually in your [bootstrap](bootstrap) file, using the full class name (or an import at the top of the file):

    // application/bootstrap.php
    Kohana\Cookie::$salt = 'your secret is safe with me';
    Kohana\Cookie::$httponly = true;

[!!] Changing the [Cookie::$salt] invalidates every cookie that was set before the change.

## Making your own classes overridable

If you are writing a module and want other developers to be able to replace one of your classes without editing your files, resolve it with [Kohana::find_class] instead of referring to it directly:

    // Use App\Widget when the application provides it, otherwise Kohana\Widget
    $class = Kohana::find_class('Widget');

    $widget = new $class;

An application can then override the class by defining `App\Widget` in `application/classes/Widget.php`, in the same way the framework's own controllers, models and drivers are overridden.

## Multiple Levels of Extension

Every class can be extended again, so functionality can be layered. For example, this application defines a template controller for all of its pages:

    <?php

    // application/classes/Controller/Template.php

    namespace App\Controller;

    use Kohana\Controller\Template as BaseTemplate;

    abstract class Template extends BaseTemplate
    {
        // Shared by every page in the application
    }

Pages then extend that class instead of the framework's:

    <?php

    // application/classes/Controller/Welcome.php

    namespace App\Controller;

    class Welcome extends Template
    {
        // ...
    }

Keep base classes free of application specific behaviour, so that the classes extending them stay reusable.
