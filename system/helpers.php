<?php

use Kohana\Translation\Translator;

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
    function __(string $string, ?array $values = null, string $lang = 'en-us'): string
    {
        if ($lang !== Translator::$lang) {
            // The message and target languages are different
            // Get the translation for this message
            $string = Translator::get($string);
        }

        return empty($values) ? $string : strtr($string, $values);
    }

}
