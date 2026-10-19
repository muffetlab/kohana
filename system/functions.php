<?php

/**
 * Kohana global helper functions.
 *
 * @package    Kohana
 * @category   Helpers
 * @author     Kohana Team
 * @copyright  (c) 2008-2013 Kohana Team
 * @license    https://kohana.top/license
 */

use Kohana\I18n;

if (!function_exists('__')) {
    /**
     * Kohana translation/internationalization function. The PHP function
     * [strtr](https://www.php.net/strtr) is used for replacing parameters.
     *
     *    __('Welcome back, :user', [':user' => $username]);
     *
     * [!!] The target language is defined by [I18n::$lang].
     *
     * @param string $string text to translate
     * @param array|null $values values to replace in the translated text
     * @param string $lang source language
     * @return  string
     * @uses    I18n::get
     */
    function __(string $string, array $values = null, string $lang = 'en-us'): string
    {
        if ($lang !== I18n::$lang) {
            // The message and target languages are different
            // Get the translation for this message
            $string = I18n::get($string);
        }

        return empty($values) ? $string : strtr($string, $values);
    }
}
