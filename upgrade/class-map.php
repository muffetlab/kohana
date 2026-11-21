<?php

/**
 * Kohana 3.5 class name => Kohana 4 (PHP 8.5) class name.
 *
 * Used by the framework's own migration and by applications through
 * upgrade/rector.php (RenameClassRector). `Kohana_*` prefixed names map to the
 * same class as their transparent extension, which no longer exists.
 * A null value means the class was removed.
 */
$map = [
    // Foundation
    'Kohana' => 'Kohana\Foundation\Application',
    'Kohana_Core' => 'Kohana\Foundation\Application',
    'Kohana_Exception' => 'Kohana\Foundation\KohanaException',
    'Kohana_Kohana_Exception' => 'Kohana\Foundation\KohanaException',
    'Model' => 'Kohana\Foundation\Model',
    'Debug' => 'Kohana\Foundation\Debug',
    'Profiler' => 'Kohana\Foundation\Profiler',

    // Support
    'Arr' => 'Kohana\Support\Arr',
    'Text' => 'Kohana\Support\Str',
    'UTF8' => 'Kohana\Support\Utf8',
    'UTF8_Exception' => 'Kohana\Support\Utf8Exception',
    'Inflector' => 'Kohana\Support\Inflector',
    'Num' => 'Kohana\Support\Number',
    'Date' => 'Kohana\Support\Date',
    'Feed' => 'Kohana\Support\Feed',

    // Config
    'Config' => 'Kohana\Config\Repository',
    'Config_Group' => 'Kohana\Config\Group',
    'Config_Source' => 'Kohana\Config\Contracts\Source',
    'Config_Reader' => 'Kohana\Config\Contracts\Reader',
    'Config_Writer' => 'Kohana\Config\Contracts\Writer',
    'Config_File' => 'Kohana\Config\FileReader',
    'Config_File_Reader' => 'Kohana\Config\FileReader',
    'Config_Database' => 'Kohana\Config\DatabaseWriter',
    'Config_Database_Reader' => 'Kohana\Config\DatabaseReader',
    'Config_Database_Writer' => 'Kohana\Config\DatabaseWriter',

    // HTTP
    'HTTP' => 'Kohana\Http\Http',
    'HTTP_Header' => 'Kohana\Http\HeaderBag',
    'HTTP_Message' => 'Kohana\Http\Contracts\Message',
    'HTTP_Request' => 'Kohana\Http\Contracts\Request',
    'HTTP_Response' => 'Kohana\Http\Contracts\Response',
    'HTTP_Cache' => 'Kohana\Http\Cache',
    'HTTP_Exception' => 'Kohana\Http\Exceptions\HttpException',
    'HTTP_Exception_Expected' => 'Kohana\Http\Exceptions\ExpectedHttpException',
    'HTTP_Exception_Redirect' => 'Kohana\Http\Exceptions\RedirectHttpException',
    'Request' => 'Kohana\Http\Request',
    'Request_Exception' => 'Kohana\Http\Exceptions\RequestException',
    'Request_Client' => 'Kohana\Http\Client\Client',
    'Request_Client_Internal' => 'Kohana\Http\Client\InternalClient',
    'Request_Client_External' => 'Kohana\Http\Client\ExternalClient',
    'Request_Client_Curl' => 'Kohana\Http\Client\CurlClient',
    'Request_Client_HTTP' => 'Kohana\Http\Client\HttpClient',
    'Request_Client_Stream' => 'Kohana\Http\Client\StreamClient',
    'Request_Client_Recursion_Exception' => 'Kohana\Http\Client\RecursionException',
    'Response' => 'Kohana\Http\Response',
    'Upload' => 'Kohana\Http\Upload',
    'Cookie' => 'Kohana\Cookie\Cookie',

    // Routing
    'Route' => 'Kohana\Routing\Route',
    'URL' => 'Kohana\Routing\Url',
    'Controller' => 'Kohana\Routing\Controller',
    'Controller_Template' => 'Kohana\Routing\TemplateController',

    // View & HTML
    'View' => 'Kohana\View\View',
    'View_Exception' => 'Kohana\View\ViewException',
    'HTML' => 'Kohana\Html\Html',
    'Form' => 'Kohana\Html\Form',

    // Validation
    'Validation' => 'Kohana\Validation\Validator',
    'Validation_Exception' => 'Kohana\Validation\ValidationException',
    'Valid' => 'Kohana\Validation\Rules',

    // Security & encryption
    'Security' => 'Kohana\Security\Security',
    'Encrypt' => 'Kohana\Encryption\Encrypter',
    'Encrypt_Openssl' => 'Kohana\Encryption\OpensslEncrypter',

    // Filesystem
    'File' => 'Kohana\Filesystem\File',

    // Translation
    'I18n' => 'Kohana\Translation\Translator',

    // Log
    'Log' => 'Kohana\Log\Logger',
    'Log_Writer' => 'Kohana\Log\Writers\Writer',
    'Log_File' => 'Kohana\Log\Writers\FileWriter',
    'Log_StdErr' => 'Kohana\Log\Writers\StdErrWriter',
    'Log_StdOut' => 'Kohana\Log\Writers\StdOutWriter',
    'Log_Syslog' => 'Kohana\Log\Writers\SyslogWriter',

    // Session
    'Session' => 'Kohana\Session\Session',
    'Session_Exception' => 'Kohana\Session\SessionException',
    'Session_Native' => 'Kohana\Session\NativeSession',
    'Session_Cookie' => 'Kohana\Session\CookieSession',
    'Session_Database' => 'Kohana\Session\DatabaseSession',

    // Cache
    'Cache' => 'Kohana\Cache\Cache',
    'Cache_Exception' => 'Kohana\Cache\CacheException',
    'Cache_Arithmetic' => 'Kohana\Cache\Contracts\Arithmetic',
    'Cache_Tagging' => 'Kohana\Cache\Contracts\Tagging',
    'Cache_GarbageCollect' => 'Kohana\Cache\Contracts\GarbageCollect',
    'Cache_Apcu' => 'Kohana\Cache\ApcuStore',
    'Cache_File' => 'Kohana\Cache\FileStore',
    'Cache_Memcached' => 'Kohana\Cache\MemcachedStore',
    'Cache_Sqlite' => 'Kohana\Cache\SqliteStore',
    'Cache_Memcache' => null,
    'Cache_Wincache' => null,
    'Fragment' => 'Kohana\Cache\Fragment',

    // Database
    'Database' => 'Kohana\Database\Connection',
    'Database_Exception' => 'Kohana\Database\DatabaseException',
    'Database_MySQLi' => 'Kohana\Database\MySqliConnection',
    'Database_PDO' => 'Kohana\Database\PdoConnection',
    'Database_Result' => 'Kohana\Database\Result',
    'Database_Result_Cached' => 'Kohana\Database\CachedResult',
    'Database_MySQLi_Result' => 'Kohana\Database\MySqliResult',
    'Database_Expression' => 'Kohana\Database\Query\Expression',
    'Database_Query' => 'Kohana\Database\Query\Query',
    'Database_Query_Builder' => 'Kohana\Database\Query\Builder',
    'Database_Query_Builder_Select' => 'Kohana\Database\Query\SelectBuilder',
    'Database_Query_Builder_Insert' => 'Kohana\Database\Query\InsertBuilder',
    'Database_Query_Builder_Update' => 'Kohana\Database\Query\UpdateBuilder',
    'Database_Query_Builder_Delete' => 'Kohana\Database\Query\DeleteBuilder',
    'Database_Query_Builder_Where' => 'Kohana\Database\Query\WhereBuilder',
    'Database_Query_Builder_Join' => 'Kohana\Database\Query\JoinClause',
    'DB' => 'Kohana\Database\DB',
    'Model_Database' => 'Kohana\Database\Model',

    // ORM & Auth
    'ORM' => 'Kohana\Orm\Model',
    'ORM_Validation_Exception' => 'Kohana\Orm\ValidationException',
    'Auth' => 'Kohana\Auth\Auth',
    'Auth_File' => 'Kohana\Auth\FileAuth',
    'Auth_ORM' => 'Kohana\Auth\OrmAuth',
    'Model_Auth_User' => 'Kohana\Auth\Models\User',
    'Model_Auth_Role' => 'Kohana\Auth\Models\Role',
    'Model_Auth_User_Token' => 'Kohana\Auth\Models\UserToken',
    'Model_User' => 'Kohana\Auth\Models\User',
    'Model_Role' => 'Kohana\Auth\Models\Role',
    'Model_User_Token' => 'Kohana\Auth\Models\UserToken',

    // Image
    'Image' => 'Kohana\Image\Image',
    'Image_GD' => 'Kohana\Image\GdImage',
    'Image_Imagick' => 'Kohana\Image\ImagickImage',

    // Console (Minion)
    'Minion_CLI' => 'Kohana\Console\Cli',
    'Minion_Task' => 'Kohana\Console\Command',
    'Minion_Exception' => 'Kohana\Console\ConsoleException',
    'Minion_Exception_InvalidTask' => 'Kohana\Console\InvalidCommandException',
    'Task_Help' => 'Kohana\Console\Commands\HelpCommand',

    // Testing
    'Unittest_TestCase' => 'Kohana\Testing\TestCase',
    'Unittest_Database_TestCase' => 'Kohana\Testing\DatabaseTestCase',
    'Unittest_Helpers' => 'Kohana\Testing\Helpers',
    'Unittest_Tests' => null,
    'Unittest_TestSuite' => null,
];

// HTTP_Exception_404 => Kohana\Http\Exceptions\Http404Exception, etc.
foreach ([300, 301, 302, 303, 304, 305, 307, 400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 500, 501, 502, 503, 504, 505] as $code) {
    $map["HTTP_Exception_$code"] = "Kohana\\Http\\Exceptions\\Http{$code}Exception";
}

// Every public class has a Kohana_ prefixed parent with the same target.
foreach ($map as $old => $new) {
    if (!str_starts_with($old, 'Kohana_') && $old !== 'Kohana') {
        $map += ["Kohana_$old" => $new];
    }
}

return $map;
