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

if (!function_exists('env')) {
    /**
     * Reads an environment variable, such as one loaded from the `.env` file.
     *
     *     $password = env('DB_PASSWORD', '');
     *     $debug = env('APP_DEBUG', false);
     *
     * The strings "true", "false", "null" and "empty" (also in parentheses)
     * become `true`, `false`, `null` and `''`. Surrounding quotes are removed.
     *
     * @param string $key Variable name
     * @param mixed $default Value when the variable is not set
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default instanceof Closure ? $default() : $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
