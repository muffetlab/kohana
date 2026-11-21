<?php

namespace Kohana\Http;

use Kohana\Cookie\Cookie;
use Kohana\Foundation\Application;
use Kohana\Http\Contracts\Response;
use Kohana\Support\Str;

/**
 * The Kohana_HTTP_Header class provides an Object-Orientated interface
 * to HTTP headers. This can parse header arrays returned from the
 * PHP functions `apache_request_headers()` or the `http_parse_headers()`
 * function available within the PECL HTTP library.
 *
 * @package    Kohana
 * @category   HTTP
 * @author     Kohana Team
 * @since      3.1.0
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    https://kohana.top/license
 */
class HeaderBag extends \ArrayObject
{
    // Default Accept-* quality value if none supplied
    public const DEFAULT_QUALITY = 1;

    /**
     * Parses an Accept(-*) header and detects the quality
     *
     * @param   array   $parts  Accept header parts
     * @return  array
     * @since   3.2.0
     */
    public static function acceptQuality(array $parts): array
    {
        $parsed = [];

        // Resource light iteration
        $parts_keys = array_keys($parts);
        foreach ($parts_keys as $key) {
            $value = trim(str_replace(["\r", "\n"], '', $parts[$key]));

            $pattern = '~\b(\;\s*+)?q\s*+=\s*+([.0-9]+)~';

            // If there is no quality directive, return default
            if (!preg_match($pattern, $value, $quality)) {
                $parsed[$value] = (float) static::DEFAULT_QUALITY;
            } else {
                $quality = $quality[2];

                if ($quality[0] === '.') {
                    $quality = '0' . $quality;
                }

                // Remove the quality value from the string and apply quality
                $parsed[trim(preg_replace($pattern, '', $value, 1), '; ')] = (float) $quality;
            }
        }

        return $parsed;
    }

    /**
     * Parses the Accept header to provide the correct quality values
     * for each supplied media type.
     *
     * @link    https://www.rfc-editor.org/rfc/rfc9110.html#name-accept
     * @param string|null $accepts Accept header string to parse
     * @return  array
     * @since   3.2.0
     */
    public static function parseAcceptHeader(?string $accepts = null): array
    {
        $accepts = explode(',', (string) $accepts);

        // If there is no accept, let's accept everything
        if ($accepts === null) {
            return ['*' => ['*' => (float) static::DEFAULT_QUALITY]];
        }

        // Parse the Accept header qualities
        $accepts = static::acceptQuality($accepts);

        $parsed_accept = [];

        // This method of iteration uses less resource
        $keys = array_keys($accepts);
        foreach ($keys as $key) {
            // Extract the parts
            $parts = explode('/', $key, 2);

            // Invalid content type—bail
            if (!isset($parts[1])) {
                continue;
            }

            // Set the parsed output
            $parsed_accept[$parts[0]][$parts[1]] = $accepts[$key];
        }

        return $parsed_accept;
    }

    /**
     * Parses the `Accept-Charset:` HTTP header and returns an array containing
     * the charset and associated quality.
     *
     * @link    https://www.rfc-editor.org/rfc/rfc9110.html#name-accept-charset
     * @param string|null $charset Charset string to parse
     * @return  array
     * @since   3.2.0
     */
    public static function parseCharsetHeader(?string $charset = null): array
    {
        if ($charset === null) {
            return ['*' => (float) static::DEFAULT_QUALITY];
        }

        return static::acceptQuality(explode(',', $charset));
    }

    /**
     * Parses the `Accept-Encoding:` HTTP header and returns an array containing
     * the charsets and associated quality.
     *
     * @link    https://www.rfc-editor.org/rfc/rfc9110.html#name-accept-encoding
     * @param string|null $encoding Charset string to parse
     * @return  array
     * @since   3.2.0
     */
    public static function parseEncodingHeader(?string $encoding = null): array
    {
        // Accept everything
        if ($encoding === null) {
            return ['*' => (float) static::DEFAULT_QUALITY];
        } elseif ($encoding === '') {
            return ['identity' => (float) static::DEFAULT_QUALITY];
        } else {
            return static::acceptQuality(explode(',', $encoding));
        }
    }

    /**
     * Parses the `Accept-Language:` HTTP header and returns an array containing
     * the languages and associated quality.
     *
     * @link    https://www.rfc-editor.org/rfc/rfc9110.html#name-accept-language
     * @param string|null $language Charset string to parse
     * @return  array
     * @since   3.2.0
     */
    public static function parseLanguageHeader(?string $language = null): array
    {
        if ($language === null) {
            return ['*' => ['*' => (float) static::DEFAULT_QUALITY]];
        }

        $language = static::acceptQuality(explode(',', $language));

        $parsed_language = [];

        $keys = array_keys($language);
        foreach ($keys as $key) {
            // Extract the parts
            $parts = explode('-', $key, 2);

            // Invalid content type—bail
            if (!isset($parts[1])) {
                $parsed_language[$parts[0]]['*'] = $language[$key];
            } else {
                // Set the parsed output
                $parsed_language[$parts[0]][$parts[1]] = $language[$key];
            }
        }

        return $parsed_language;
    }

    /**
     * Generates a Cache-Control HTTP header based on the supplied array.
     *
     *     // Set the cache control headers you want to use
     *     $cache_control = [
     *         'max-age'          => 3600,
     *         'must-revalidate',
     *         'public'
     *     ];
     *
     *     // Create the cache control header, creates :
     *     // cache-control: max-age=3600, must-revalidate, public
     *     $response->headers('Cache-Control', HTTP_Header::create_cache_control($cache_control);
     *
     * @link    https://www.rfc-editor.org/rfc/rfc9111.html#name-cache-control
     * @param   array   $cache_control  Cache-Control to render to string
     * @return  string
     */
    public static function createCacheControl(array $cache_control): string
    {
        $parts = [];

        foreach ($cache_control as $key => $value) {
            $parts[] = is_int($key) ? $value : $key . '=' . $value;
        }

        return implode(', ', $parts);
    }

    /**
     * Parses the Cache-Control header and returning an array representation of the Cache-Control
     * header.
     *
     *     // Create the cache control header
     *     $response->headers('cache-control', 'max-age=3600, must-revalidate, public');
     *
     *     // Parse the cache control header
     *     if ($cache_control = HTTP_Header::parse_cache_control($response->headers('cache-control'))) {
     *          // Cache-Control header was found
     *          $maxAge = $cache_control['max-age'];
     *     }
     *
     * @param string $cache_control Array of headers
     * @return  array|false
     */
    public static function parseCacheControl(string $cache_control)
    {
        $directives = explode(',', strtolower($cache_control));

        if ($directives === false) {
            return false;
        }

        $output = [];

        foreach ($directives as $directive) {
            if (strpos($directive, '=') !== false) {
                list($key, $value) = explode('=', trim($directive), 2);

                $output[$key] = ctype_digit($value) ? (int) $value : $value;
            } else {
                $output[] = trim($directive);
            }
        }

        return $output;
    }

    /**
     * @var     array    Accept: (content) types
     */
    protected $acceptContent;

    /**
     * @var     array    Accept-Charset: parsed header
     */
    protected $acceptCharset;

    /**
     * @var     array    Accept-Encoding: parsed header
     */
    protected $acceptEncoding;

    /**
     * @var     array    Accept-Language: parsed header
     */
    protected $acceptLanguage;

    /**
     * Constructor method for [Kohana_HTTP_Header]. Uses the standard constructor
     * of the parent `ArrayObject` class.
     *
     *     $header_object = new HTTP_Header(['x-powered-by' => 'Kohana 3.1.x', 'expires' => '...']);
     *
     * @param   mixed   $input          Input array
     * @param   int     $flags          Flags
     * @param   string  $iterator_class The iterator class to use
     */
    public function __construct(array $input = [], $flags = 0, $iterator_class = 'ArrayIterator')
    {
        /**
         * HTTP header declarations should be treated as case-insensitive
         */
        $input = array_change_key_case($input);

        parent::__construct($input, $flags, $iterator_class);
    }

    /**
     * Returns the header object as a string, including
     * the terminating new line
     *
     *     // Return the header as a string
     *     echo (string) $request->headers();
     *
     * @return  string
     */
    public function __toString()
    {
        $header = '';

        foreach ($this as $key => $value) {
            // Put the keys back the Case-Convention expected
            $key = Str::ucfirst($key);

            if (is_array($value)) {
                $header .= $key . ': ' . (implode(', ', $value)) . "\r\n";
            } else {
                $header .= $key . ': ' . $value . "\r\n";
            }
        }

        return $header . "\r\n";
    }

    /**
     * Overloads `ArrayObject::offsetSet()` to enable handling of header
     * with multiple instances of the same directive. If the `$replace` flag
     * is `false`, the header will be appended rather than replacing the
     * original setting.
     *
     * @param mixed $key The index being set.
     * @param mixed $value The new value for the index.
     * @param bool $replace replace existing value
     * @return  void
     * @since   3.2.0
     */
    public function offsetSet($key, $value, bool $replace = true): void
    {
        // Ensure the index is lowercase
        $key = strtolower($key);

        if ($replace || !$this->offsetExists($key)) {
            parent::offsetSet($key, $value);
            return;
        }

        $current_value = $this->offsetGet($key);

        if (is_array($current_value)) {
            $current_value[] = $value;
        } else {
            $current_value = [$current_value, $value];
        }

        parent::offsetSet($key, $current_value);
    }

    /**
     * Overloads the `ArrayObject::offsetExists()` method to ensure keys
     * are lowercase.
     *
     * @param   string  $key
     * @return  bool
     * @since   3.2.0
     */
    public function offsetExists($key): bool
    {
        return parent::offsetExists(strtolower($key));
    }

    /**
     * Overloads the `ArrayObject::offsetUnset()` method to ensure keys
     * are lowercase.
     *
     * @param   string  $key
     * @return  void
     * @since   3.2.0
     */
    public function offsetUnset($key): void
    {
        parent::offsetUnset(strtolower($key));
    }

    /**
     * Overload the `ArrayObject::offsetGet()` method to ensure that all
     * keys passed to it are formatted correctly for this object.
     *
     * @param   string  $key  index to retrieve
     * @return  mixed
     * @since   3.2.0
     */
    public function offsetGet($key): mixed
    {
        return parent::offsetGet(strtolower($key));
    }

    /**
     * Overloads the `ArrayObject::exchangeArray()` method to ensure that
     * all keys are changed to lowercase.
     *
     * @param   mixed   $array
     * @return  array
     * @since   3.2.0
     */
    public function exchangeArray($array): array
    {
        /**
         * HTTP header declarations should be treated as case-insensitive
         */
        $array = array_change_key_case((array) $array);

        return parent::exchangeArray($array);
    }

    /**
     * Parses an HTTP Message header line and applies it to this HTTP_Header
     *
     *     $header = $response->headers();
     *     $header->parse_header_string(null, 'content-type: application/json');
     *
     * @param resource|null $resource The resource (required by cURL API)
     * @param string $header_line The line from the header to parse
     * @return  int
     * @since   3.2.0
     */
    public function parseHeaderString($resource, string $header_line): int
    {
        if (preg_match_all('/(\w[^\s:]*):[ ]*([^\r\n]*(?:\r\n[ \t][^\r\n]*)*)/', $header_line, $matches)) {
            foreach ($matches[0] as $key => $value) {
                $this->offsetSet($matches[1][$key], $matches[2][$key], false);
            }
        }

        return strlen($header_line);
    }

    /**
     * Returns the accept quality of a submitted mime type based on the
     * request `Accept:` header. If the `$explicit` argument is `true`,
     * only precise matches will be returned, excluding all wildcard (`*`)
     * directives.
     *
     *     // Accept: application/xml; application/json; q=.5; text/html; q=.2, text/*
     *     // Accept quality for application/json
     *
     *     // $quality = 0.5
     *     $quality = $request->headers()->accepts_at_quality('application/json');
     *
     *     // $quality_explicit = false
     *     $quality_explicit = $request->headers()->accepts_at_quality('text/plain', true);
     *
     * @param string $type
     * @param bool $explicit explicit check, excludes `*`
     * @return  mixed
     * @throws \Kohana\Foundation\KohanaException
     * @since   3.2.0
     */
    public function acceptsAtQuality(string $type, bool $explicit = false)
    {
        // Parse Accept header if required
        if ($this->acceptContent === null) {
            if ($this->offsetExists('Accept')) {
                $accept = $this->offsetGet('Accept');
            } else {
                $accept = '*/*';
            }

            $this->acceptContent = static::parseAcceptHeader($accept);
        }

        // If not a real mime, try and find it in config
        if (strpos($type, '/') === false) {
            $mime = Application::$config->load('mimes.' . $type);

            if ($mime === null) {
                return false;
            }

            $quality = false;

            foreach ($mime as $_type) {
                $quality_check = $this->acceptsAtQuality($_type, $explicit);
                $quality = $quality_check > $quality ? $quality_check : $quality;
            }

            return $quality;
        }

        $parts = explode('/', $type, 2);

        if (isset($this->acceptContent[$parts[0]][$parts[1]])) {
            return $this->acceptContent[$parts[0]][$parts[1]];
        } elseif ($explicit === true) {
            return false;
        } else {
            if (isset($this->acceptContent[$parts[0]]['*'])) {
                return $this->acceptContent[$parts[0]]['*'];
            } elseif (isset($this->acceptContent['*']['*'])) {
                return $this->acceptContent['*']['*'];
            } else {
                return false;
            }
        }
    }

    /**
     * Returns the preferred response content type based on the Accept header
     * quality settings. If items have the same quality value, the first item
     * found in the array supplied as `$types` will be returned.
     *
     *     // Get the preferred acceptable content type
     *     // Accept: text/html, application/json; q=.8, text/*
     *     $result = $header->preferred_accept([
     *         'text/html'
     *         'text/rtf',
     *         'application/json'
     *     ]); // $result = 'application/json'
     *
     *     $result = $header->preferred_accept([
     *         'text/rtf',
     *         'application/xml'
     *     ], true); // $result = false (none matched explicitly)
     *
     *
     * @param array $types the content types to examine
     * @param bool $explicit only allow explicit references, no wildcards
     * @return  string  name of the preferred content type
     * @throws \Kohana\Foundation\KohanaException
     * @since   3.2.0
     */
    public function preferredAccept(array $types, bool $explicit = false)
    {
        $preferred = false;
        $ceiling = 0;

        foreach ($types as $type) {
            $quality = $this->acceptsAtQuality($type, $explicit);

            if ($quality > $ceiling) {
                $preferred = $type;
                $ceiling = $quality;
            }
        }

        return $preferred;
    }

    /**
     * Returns the quality of the supplied `$charset` argument. This method
     * will automatically parse the `Accept-Charset` header if present and
     * return the associated resolved quality value.
     *
     *      // Accept-Charset: utf-8, utf-16; q=.8, iso-8859-1; q=.5
     *      $quality = $header->accepts_charset_at_quality('utf-8');
     *            // $quality = (float) 1
     *
     * @param string $charset Charset to examine
     * @return  float   the quality of the charset
     * @since   3.2.0
     */
    public function acceptsCharsetAtQuality(string $charset): float
    {
        if ($this->acceptCharset === null) {
            if ($this->offsetExists('Accept-Charset')) {
                $charset_header = strtolower($this->offsetGet('Accept-Charset'));
                $this->acceptCharset = static::parseCharsetHeader($charset_header);
            } else {
                $this->acceptCharset = static::parseCharsetHeader();
            }
        }

        $charset = strtolower($charset);

        if (isset($this->acceptCharset[$charset])) {
            return $this->acceptCharset[$charset];
        } elseif (isset($this->acceptCharset['*'])) {
            return $this->acceptCharset['*'];
        } elseif ($charset === 'iso-8859-1') {
            return 1.0;
        }

        return 0.0;
    }

    /**
     * Returns the preferred charset from the supplied array `$charsets` based
     * on the `Accept-Charset` header directive.
     *
     *      // Accept-Charset: utf-8, utf-16; q=.8, iso-8859-1; q=.5
     *      $charset = $header->preferred_charset([
     *          'utf-10', 'ascii', 'utf-16', 'utf-8'
     *      ]); // $charset = 'utf-8'
     *
     * @param   array   $charsets   charsets to test
     * @return  mixed   preferred charset or `false`
     * @since   3.2.0
     */
    public function preferredCharset(array $charsets)
    {
        $preferred = false;
        $ceiling = 0;

        foreach ($charsets as $charset) {
            $quality = $this->acceptsCharsetAtQuality($charset);

            if ($quality > $ceiling) {
                $preferred = $charset;
                $ceiling = $quality;
            }
        }

        return $preferred;
    }

    /**
     * Returns the quality of the `$encoding` type passed to it. Encoding
     * is usually compression such as `gzip`, but could be some other
     * message encoding algorithm. This method allows explicit checks to be
     * done ignoring wildcards.
     *
     *      // Accept-Encoding: compress, gzip, *; q=.5
     *      $encoding = $header->accepts_encoding_at_quality('gzip');
     *      // $encoding = (float) 1.0s
     *
     * @param string $encoding Encoding type to interrogate
     * @param bool $explicit Explicit check, ignoring wildcards and `identity`
     * @return  float
     * @since   3.2.0
     */
    public function acceptsEncodingAtQuality(string $encoding, bool $explicit = false): float
    {
        if ($this->acceptEncoding === null) {
            if ($this->offsetExists('Accept-Encoding')) {
                $encoding_header = $this->offsetGet('Accept-Encoding');
            } else {
                $encoding_header = null;
            }

            $this->acceptEncoding = static::parseEncodingHeader($encoding_header);
        }

        // Normalize the encoding
        $encoding = strtolower($encoding);

        if (isset($this->acceptEncoding[$encoding])) {
            return $this->acceptEncoding[$encoding];
        }

        if ($explicit === false) {
            if (isset($this->acceptEncoding['*'])) {
                return $this->acceptEncoding['*'];
            } elseif ($encoding === 'identity') {
                return (float) static::DEFAULT_QUALITY;
            }
        }

        return 0.0;
    }

    /**
     * Returns the preferred message encoding type based on quality, and can
     * optionally ignore wildcard references. If two or more encodings have the
     * same quality, the first listed in `$encodings` will be returned.
     *
     *     // Accept-Encoding: compress, gzip, *; q.5
     *     $encoding = $header->preferred_encoding([
     *          'gzip', 'bzip', 'blowfish'
     *     ]);
     *     // $encoding = 'gzip';
     *
     * @param   array   $encodings  encodings to test against
     * @param bool $explicit Explicit check, if `true` wildcards are excluded
     * @return  mixed
     * @since   3.2.0
     */
    public function preferredEncoding(array $encodings, bool $explicit = false)
    {
        $ceiling = 0;
        $preferred = false;

        foreach ($encodings as $encoding) {
            $quality = $this->acceptsEncodingAtQuality($encoding, $explicit);

            if ($quality > $ceiling) {
                $ceiling = $quality;
                $preferred = $encoding;
            }
        }

        return $preferred;
    }

    /**
     * Returns the quality of `$language` supplied, optionally ignoring
     * wildcards if `$explicit` is set to a non-`false` value. If the quality
     * is not found, `0.0` is returned.
     *
     *     // Accept-Language: en-us, en-gb; q=.7, en; q=.5
     *     $lang = $header->accepts_language_at_quality('en-gb');
     *     // $lang = (float) 0.7
     *
     *     $lang2 = $header->accepts_language_at_quality('en-au');
     *     // $lang2 = (float) 0.5
     *
     *     $lang3 = $header->accepts_language_at_quality('en-au', true);
     *     // $lang3 = (float) 0.0
     *
     * @param string $language Language to interrogate
     * @param bool $explicit Explicit interrogation, `true` ignores wildcards
     * @return  float
     * @since   3.2.0
     */
    public function acceptsLanguageAtQuality(string $language, bool $explicit = false): float
    {
        if ($this->acceptLanguage === null) {
            if ($this->offsetExists('Accept-Language')) {
                $language_header = strtolower($this->offsetGet('Accept-Language'));
            } else {
                $language_header = null;
            }

            $this->acceptLanguage = static::parseLanguageHeader($language_header);
        }

        // Normalize the language
        $language_parts = explode('-', strtolower($language), 2);

        if (isset($this->acceptLanguage[$language_parts[0]])) {
            if (isset($language_parts[1])) {
                if (isset($this->acceptLanguage[$language_parts[0]][$language_parts[1]])) {
                    return $this->acceptLanguage[$language_parts[0]][$language_parts[1]];
                } elseif ($explicit === false && isset($this->acceptLanguage[$language_parts[0]]['*'])) {
                    return $this->acceptLanguage[$language_parts[0]]['*'];
                }
            } elseif (isset($this->acceptLanguage[$language_parts[0]]['*'])) {
                return $this->acceptLanguage[$language_parts[0]]['*'];
            }
        }

        if ($explicit === false && isset($this->acceptLanguage['*'])) {
            return $this->acceptLanguage['*'];
        }

        return 0.0;
    }

    /**
     * Returns the preferred language from the supplied array `$languages` based
     * on the `Accept-Language` header directive.
     *
     *      // Accept-Language: en-us, en-gb; q=.7, en; q=.5
     *      $lang = $header->preferred_language([
     *          'en-gb', 'en-au', 'fr', 'es'
     *      ]); // $lang = 'en-gb'
     *
     * @param   array   $languages
     * @param bool $explicit
     * @return  mixed
     * @since   3.2.0
     */
    public function preferredLanguage(array $languages, bool $explicit = false)
    {
        $ceiling = 0;
        $preferred = false;

        foreach ($languages as $language) {
            $quality = $this->acceptsLanguageAtQuality($language, $explicit);

            if ($quality > $ceiling) {
                $ceiling = $quality;
                $preferred = $language;
            }
        }

        return $preferred;
    }

    /**
     * Sends headers to the php processor, or supplied `$callback` argument.
     * This method formats the headers correctly for output, re-instating their
     * capitalization for transmission.
     *
     * [!!] if you supply a custom header handler via `$callback`, it is
     *  recommended that `$response` is returned
     *
     * @param \Kohana\Http\Contracts\Response|null $response header to send
     * @param bool $replace replace existing value
     * @param callable|null $callback optional callback to replace PHP header function
     * @return  mixed
     * @throws \Kohana\Foundation\KohanaException
     * @since   3.2.0
     */
    public function sendHeaders(?Response $response = null, bool $replace = false, ?callable $callback = null)
    {
        $protocol = $response->protocol();
        $status = $response->status();

        // Create the response header
        $processed_headers = [$protocol . ' ' . $status . ' ' . \Kohana\Http\Response::$messages[$status]];

        // Get the headers array
        $headers = $response->headers()->getArrayCopy();

        foreach ($headers as $header => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $processed_headers[] = Str::ucfirst($header) . ': ' . $value;
        }

        if (!isset($headers['content-type'])) {
            $processed_headers[] = 'Content-Type: ' . Application::$contentType . '; charset=' . Application::$charset;
        }

        if (Application::$expose && !isset($headers['x-powered-by'])) {
            $processed_headers[] = 'X-Powered-By: ' . Application::version();
        }

        // Get the cookies and apply
        if ($cookies = $response->cookie()) {
            $processed_headers['Set-Cookie'] = $cookies;
        }

        if (is_callable($callback)) {
            // Use the callback method to set header
            return call_user_func($callback, $response, $processed_headers, $replace);
        } else {
            $this->sendHeadersToPhp($processed_headers, $replace);
            return $response;
        }
    }

    /**
     * Sends the supplied headers to the PHP output buffer. If cookies
     * are included in the message they will be handled appropriately.
     *
     * @param array $headers headers to send to php
     * @param bool $replace replace existing headers
     * @return  self
     * @throws \Kohana\Foundation\KohanaException
     * @since   3.2.0
     */
    protected function sendHeadersToPhp(array $headers, bool $replace): self
    {
        // If the headers have been sent, get out
        if (headers_sent()) {
            return $this;
        }

        foreach ($headers as $key => $line) {
            if ($key === 'Set-Cookie' && is_array($line)) {
                // Send cookies
                foreach ($line as $name => $value) {
                    Cookie::set($name, $value['value'], $value['expiration']);
                }

                continue;
            }

            header($line, $replace);
        }

        return $this;
    }

}
