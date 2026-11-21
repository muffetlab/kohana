<?php

namespace Kohana\Translation;

use Kohana\Foundation\Application;

/**
 * Internationalization (i18n) class. Provides language loading and translation
 * methods without dependencies on [gettext](https://www.php.net/gettext).
 *
 * Typically, this class would never be used directly, but used via the __()
 * function, which loads the message and replaces parameters:
 *
 *     // Display a translated message
 *     echo __('Hello, world');
 *
 *     // With parameter replacement
 *     echo __('Hello, :user', [':user' => $username]);
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class Translator
{
    /**
     * @var  string   target language: en-us, es-es, zh-cn, etc.
     */
    public static $lang = 'en-us';

    /**
     * @var  string  source language: en-us, es-es, zh-cn, etc.
     */
    public static $source = 'en-us';

    /**
     * @var  array  cache of loaded languages
     */
    protected static $_cache = [];

    /**
     * Get and set the target language.
     *
     *     // Get the current language
     *     $lang = I18n::lang();
     *
     *     // Change the current language to Spanish
     *     I18n::lang('es-es');
     *
     * @param string|null $lang New language setting
     * @return  string
     * @since   3.0.2
     */
    public static function lang(?string $lang = null): string
    {
        if ($lang) {
            // Normalize the language
            \Kohana\Translation\Translator::$lang = strtolower(str_replace([' ', '_'], '-', $lang));
        }

        return \Kohana\Translation\Translator::$lang;
    }

    /**
     * Returns translation of a string. If no translation exists, the original
     * string will be returned. No parameters are replaced.
     *
     *     $hello = I18n::get('Hello friends, my name is :name');
     *
     * @param string $string Text to translate
     * @param string|null $lang Target language
     * @return  string
     */
    public static function get(string $string, ?string $lang = null): string
    {
        if (!$lang) {
            // Use the global target language
            $lang = \Kohana\Translation\Translator::$lang;
        }

        // Load the translation table for this language
        $table = \Kohana\Translation\Translator::load($lang);

        // Return the translated string if it exists
        return $table[$string] ?? $string;
    }

    /**
     * Returns the translation table for a given language.
     *
     *     // Get all defined Spanish messages
     *     $messages = I18n::load('es-es');
     *
     * @param string $lang Language to load
     * @return  array
     */
    public static function load(string $lang): array
    {
        if (isset(\Kohana\Translation\Translator::$_cache[$lang])) {
            return \Kohana\Translation\Translator::$_cache[$lang];
        }

        // New translation table
        $table = [];

        // Split the language: language, region, locale, etc.
        $parts = explode('-', $lang);

        do {
            // Create a path for this set of parts
            $path = implode(DIRECTORY_SEPARATOR, $parts);

            if ($files = Application::find_file('i18n', $path, null, true)) {
                $t = [];
                foreach ($files as $file) {
                    // Merge the language strings into the sub table
                    $t = array_merge($t, (array) Application::load($file));
                }

                // Append the sub table, preventing less specific language
                // files from overloading more specific files
                $table += $t;
            }

            // Remove the last part
            array_pop($parts);
        } while ($parts);

        // Cache the translation table locally
        return \Kohana\Translation\Translator::$_cache[$lang] = $table;
    }

}
