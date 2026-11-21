<?php

namespace Kohana\Http;

use http\Env;
use http\Header;
use Kohana\Foundation\KohanaException;
use Kohana\Http\Exceptions\HttpException;
use Kohana\Http\Exceptions\RedirectHttpException;

/**
 * Contains the most low-level helpers methods in Kohana:
 *
 * - Environment initialization
 * - Locating files within the cascading filesystem
 * - Auto-loading and transparent extension of classes
 * - Variable and path debugging
 *
 * @package    Kohana
 * @category   HTTP
 * @author     Kohana Team
 * @since      3.1.0
 * @copyright  (c) 2008-2014 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class Http
{
    /**
     * @var string The default protocol to use if it cannot be detected
     */
    public static $protocol = 'HTTP/1.1';

    /**
     * Issues an HTTP redirect.
     *
     * @param string $uri URI to redirect to
     * @param int $code HTTP Status code to use for the redirect
     * @throws \Kohana\Http\Exceptions\RedirectHttpException
     * @throws \Kohana\Foundation\KohanaException
     */
    public static function redirect(string $uri = '', int $code = 302)
    {
        $e = HttpException::factory($code);

        if (!$e instanceof RedirectHttpException) {
            throw new KohanaException('Invalid redirect code \':code\'', [':code' => $code]);
        }

        throw $e->location($uri);
    }

    /**
     * Checks the browser cache to see the response needs to be returned,
     * execution will halt and a 304 Not Modified will be sent if the
     * browser cache is up-to-date.
     *
     * @param \Kohana\Http\Request $request Request
     * @param \Kohana\Http\Response $response Response
     * @param string|null $etag Resource ETag
     * @return \Kohana\Http\Response
     * @throws \Kohana\Http\Exceptions\RequestException
     */
    public static function checkCache(Request $request, Response $response, ?string $etag = null): Response
    {
        // Generate an etag if necessary
        if ($etag === null) {
            $etag = $response->generateEtag();
        }

        // Set the ETag header
        $response->headers('etag', $etag);

        // Add the Cache-Control header if it is not already set
        // This allows etags to be used with max-age, etc.
        if ($response->headers('cache-control')) {
            $response->headers('cache-control', $response->headers('cache-control') . ', must-revalidate');
        } else {
            $response->headers('cache-control', 'must-revalidate');
        }

        // Check if we have a matching etag
        if ($request->headers('if-none-match') && (string) $request->headers('if-none-match') === $etag) {
            // No need to send data again
            throw HttpException::factory(304)->headers('etag', $etag);
        }

        return $response;
    }

    /**
     * Parses an HTTP header string into an associative array
     *
     * @param string $header_string Header string to parse
     * @return  \Kohana\Http\HeaderBag
     */
    public static function parseHeaderString(string $header_string): HeaderBag
    {
        // If the PECL HTTP extension is loaded
        if (extension_loaded('http')) {
            // Use the fast method to parse header string
            $headers = version_compare(phpversion('http'), '2.0.0', '>=') ?
                Header::parse($header_string) :
                http_parse_headers($header_string);
            return new HeaderBag($headers);
        }

        // Otherwise we use the slower PHP parsing
        $headers = [];

        // Match all HTTP headers
        if (preg_match_all('/(\w[^\s:]*):[ ]*([^\r\n]*(?:\r\n[ \t][^\r\n]*)*)/', $header_string, $matches)) {
            // Parse each matched header
            foreach ($matches[0] as $key => $value) {
                // If the header has not already been set
                if (!isset($headers[$matches[1][$key]])) {
                    // Apply the header directly
                    $headers[$matches[1][$key]] = $matches[2][$key];
                }
                // Otherwise there is an existing entry
                else {
                    // If the entry is an array
                    if (is_array($headers[$matches[1][$key]])) {
                        // Apply the new entry to the array
                        $headers[$matches[1][$key]][] = $matches[2][$key];
                    }
                    // Otherwise create a new array with the entries
                    else {
                        $headers[$matches[1][$key]] = [
                            $headers[$matches[1][$key]],
                            $matches[2][$key],
                        ];
                    }
                }
            }
        }

        // Return the headers
        return new HeaderBag($headers);
    }

    /**
     * Parses the HTTP request headers and returns an array containing
     * key value pairs. This method is slow, but provides an accurate
     * representation of the HTTP request.
     *
     *      // Get http headers into the request
     *      $request->headers = HTTP::request_headers();
     *
     * @return  \Kohana\Http\HeaderBag
     */
    public static function requestHeaders(): HeaderBag
    {
        // If running on apache server
        if (function_exists('apache_request_headers')) {
            // Return the much faster method
            return new HeaderBag(apache_request_headers());
        }
        // If the PECL HTTP tools are installed
        elseif (extension_loaded('http')) {
            // Return the much faster method
            $headers = version_compare(phpversion('http'), '2.0.0', '>=') ?
                Env::getRequestHeader() :
                http_get_request_headers();
            return new HeaderBag($headers);
        }

        // Set up the output
        $headers = [];

        // Parse the content type
        if (!empty($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        // Parse the content length
        if (!empty($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        foreach ($_SERVER as $key => $value) {
            // If there is no HTTP header here, skip
            if (strpos($key, 'HTTP_') !== 0) {
                continue;
            }

            // This is a dirty hack to ensure HTTP_X_FOO_BAR becomes X-FOO-BAR
            $headers[str_replace('_', '-', substr($key, 5))] = $value;
        }

        return new HeaderBag($headers);
    }

    /**
     * Processes an array of key value pairs and encodes
     * the values to meet RFC 3986
     *
     * @param   array   $params  Params
     * @return  string
     */
    public static function wwwFormUrlencode(array $params = []): string
    {
        if (!$params) {
            return '';
        }

        $encoded = [];

        foreach ($params as $key => $value) {
            $encoded[] = $key . '=' . rawurlencode($value);
        }

        return implode('&', $encoded);
    }

}
