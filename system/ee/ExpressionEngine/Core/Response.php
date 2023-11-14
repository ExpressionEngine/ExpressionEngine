<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Core;

/**
 * Core Response
 */
class Response
{
    protected $body = '';

    protected $status = 200;

    protected $headers = array();

    protected $compress = false;

    /**
     *
     */
    public function setBody($str)
    {
        if (is_array($str)) {
            $str = json_encode($str);
            $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        $this->body = $str;
    }

    /**
     *
     */
    public function appendBody($str)
    {
        $this->body .= $str;
    }

    public function hasHeader($header)
    {
        return array_key_exists($header, $this->headers);
    }

    public function getHeader($header)
    {
        if ($this->hasHeader($header)) {
            return $this->headers[$header];
        }

        return null;
    }

    /**
     *
     */
    public function setHeader($header, $value = null)
    {
        if (! isset($value)) {
            list($header, $value) = explode(':', $header, 2);
        }

        $this->headers[$header] = $value;
    }

    /**
     * Sets the status
     *
     * @throws TypeError
     * @param int $status The status code
     * @return void
     */
    public function setStatus($status)
    {
        if (is_numeric($status)) {
            $this->status = $status;

            return;
        }

        throw new \TypeError("setStatus expects a number");
    }

    /**
     *
     */
    public function send()
    {
        if (! $this->body) {
            foreach ($this->headers as $name => $value) {
                $GLOBALS['OUT']->headers[] = array($name . ': ' . $value, true);
            }

            // smoke and mirrors to support the old style
            return $GLOBALS['OUT']->_display('', $this->status);
        }

        $this->sendHeaders();
        $this->sendBody();
    }

    /**
     *
     */
    public function enableCompression()
    {
        if ($this->supportsCompression()) {
            $this->compress = true;
        }
    }

    /**
     *
     */
    public function disableCompression()
    {
        $this->compress = false;
    }

    /**
     *
     */
    public function supportsCompression()
    {
        return (
            $this->clientSupportsCompression() &&
            $this->serverSupportsCompression()
        );
    }

    /**
     *
     */
    public function compressionEnabled()
    {
        return $this->compress == true && $this->status != 304;
    }

    /**
     *
     */
    protected function sendHeaders()
    {
        foreach ($this->headers as $name => $value) {
            @header($name . ': ' . $value);
        }
    }

    /**
     *
     */
    protected function sendBody()
    {
        if ($this->compressionEnabled()) {
            ob_start('ob_gzhandler');
        }

        echo $this->body;
    }

    /**
     *
     */
    protected function clientSupportsCompression()
    {
        $header = 'HTTP_ACCEPT_ENCODING';

        return (
            isset($_SERVER[$header]) &&
            strpos($_SERVER[$header], 'gzip') !== false
        );
    }

    /**
     *
     */
    protected function serverSupportsCompression()
    {
        $zlib_enabled = (bool) @ini_get('zlib.output_compression');

        return $zlib_enabled == false && extension_loaded('zlib');
    }
}

// EOF
