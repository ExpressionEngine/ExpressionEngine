<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Curl;

/**
 * Curl Request
 */
abstract class Request
{
    protected $headers = array();
    protected $headersLowercase = array();
    public $config;
    public $callback;

    public function __construct($url, $data, $callback = null)
    {
        if (! function_exists('curl_version')) {
            throw new \Exception(lang('curl_not_installed'));
        }

        $this->config = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
        );

        // If supported attempt to use the native root certificate store
        if (defined('CURLSSLOPT_NATIVE_CA') && version_compare(curl_version()['version'], '7.71', '>=')) {
            $this->config[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }

        foreach ($data as $key => $val) {
            if (substr($key, 0, 7) == "CURLOPT") {
                $this->config[constant($key)] = $val;
            }
        }

        if (! empty($callback)) {
            $this->callback = $callback;
        }
    }

    /**
     * Convert public object properties before PHP 8.6 query encoding.
     * Circular references are rejected on this path; earlier PHP stays native.
     *
     * @throws \InvalidArgumentException When PHP 8.6 query data is circular.
     */
    protected function buildQuery($data)
    {
        if (PHP_VERSION_ID < 80600) {
            return http_build_query($data);
        }

        return http_build_query($this->queryData($data));
    }

    private function queryData($data, array $parents = array(), $identity = null)
    {
        // Native encoding accepts nested backed enums and rejects unit enums.
        if ($data instanceof \UnitEnum || (!is_array($data) && !is_object($data))) {
            return $data;
        }

        if (is_object($data)) {
            $identity = 'object:' . spl_object_id($data);
        }
        if ($identity !== null) {
            if (isset($parents[$identity])) {
                throw new \InvalidArgumentException('Query data cannot contain circular references.');
            }
            $parents[$identity] = true;
        }

        if (is_object($data)) {
            // Exclude Request's visibility privileges and internal virtual state.
            $publicProperties = \Closure::bind(static function ($object) {
                return get_object_vars($object);
            }, null, null);
            $data = $publicProperties($data);
        }

        $result = array();
        foreach ($data as $key => $value) {
            // This method only runs on PHP 8.6, where ReflectionReference exists.
            $reference = is_array($value) ? \ReflectionReference::fromArrayElement($data, $key) : null;
            $identity = $reference === null ? null : 'array:' . $reference->getId();
            $result[$key] = $this->queryData($value, $parents, $identity);
        }
        return $result;
    }

    public function exec()
    {
        $curl = curl_init();
        curl_setopt_array($curl, $this->config);

        $response = curl_exec($curl);

        if ($response === false) {
            throw new \Exception(curl_error($curl));
        }

        list($headers, $data) = explode("\r\n\r\n", $response, 2);

        $this->setHeaders($headers, $curl);

        if (PHP_VERSION_ID < 80000) {
            curl_close($curl);
        }
        unset($curl);

        if (! empty($this->callback)) {
            return call_user_func($this->callback, $data);
        }

        return $this->callback($data);
    }

    public function callback($data)
    {
        return $data;
    }

    /**
     * Given a string of headers from a cURL response, creates an associative array
     * class property of the headers
     *
     * @param	string		$headers	String output of headers from cURL
     * @param	resource	$curl		Current cURL resource
     */
    protected function setHeaders($headers, $curl)
    {
        $this->headers = curl_getinfo($curl);

        $headers = explode("\r\n", $headers);
        array_shift($headers);
        foreach ($headers as $i => $line) {
            list($key, $value) = explode(': ', $line);

            $this->headers[$key] = $value;
        }

        $this->setHeadersLowercase();
    }

    /**
     * Returns the requested header value
     *
     * @param	string	$key 	Header to return value for
     * @return	string	Value of header, or FALSE if header not found
     */
    public function getHeader($key)
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }

        // check if key exists in lowercase array.. will return value or false
        return $this->getHeaderLowercase($key);
    }

    /**
     * Sets all headers to be lowercase
     */
    protected function setHeadersLowercase()
    {
        if (!empty($this->headers)) {
            // loop over headers and set both key and value to lowecase
            foreach ($this->headers as $key => $value) {
                if (!empty($value)) {
                    // Please note.. this is only lowercase key.. not value
                    // lowercase value will kill the code ee signiture one click download check.
                    $this->headersLowercase[strtolower($key)] = $value;
                }
            }
        }
    }

    /**
     * Returns the requested header value all lowercase
     *
     * @param	string	$key	Header to return value for
     * @return	string	Value of header, or FALSE if header not found
     */
    public function getHeaderLowercase($key)
    {
        $key = strtolower($key);

        if (isset($this->headersLowercase[$key])) {
            return $this->headersLowercase[$key];
        }

        return false;
    }
}

// EOF
