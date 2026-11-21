<?php

/**
 * Absolute paths of the project's directories. Every entry point (public/index.php,
 * the kohana console, the test bootstrap) loads this file first.
 */
defined('BASEPATH') or define('BASEPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
defined('DOCROOT') or define('DOCROOT', BASEPATH . 'public' . DIRECTORY_SEPARATOR);
defined('APPPATH') or define('APPPATH', BASEPATH . 'app' . DIRECTORY_SEPARATOR);
defined('MODPATH') or define('MODPATH', BASEPATH . 'modules' . DIRECTORY_SEPARATOR);
defined('SYSPATH') or define('SYSPATH', BASEPATH . 'system' . DIRECTORY_SEPARATOR);
defined('VENDOR_PATH') or define('VENDOR_PATH', BASEPATH . 'vendor' . DIRECTORY_SEPARATOR);

defined('KOHANA_START_TIME') or define('KOHANA_START_TIME', microtime(true));
defined('KOHANA_START_MEMORY') or define('KOHANA_START_MEMORY', memory_get_usage());
