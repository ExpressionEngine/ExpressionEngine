<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Filesystem;

class TempFileFactory
{
    public static $fallbackRegistered = false;

    public static function make()
    {
        return function_exists('tmpfile') ? tmpfile() : static::fallback();
    }

    /**
     *
     * Polyfill for missing tmpfile()
     * https://www.php.net/manual/en/function.tmpfile.php
     *
     * tmpfile() description from php.net:
     * Creates a temporary file with a unique name in read-write-binary (w+b) mode and returns a file handle.
     * The file is automatically removed when closed (for example, by calling fclose(), or when there
     * are no remaining references to the file handle returned by tmpfile()), or when the script ends.
     *
     * @return resource|false
     */
    public static function fallback()
    {
        if (!static::$fallbackRegistered) {
            ee()->load->library('logger');
            ee()->logger->developer(
                "Your system has disabled support for PHP's tmpfile(). " .
                "For best results please enable tmpfile in your php.ini settings",
                true,
                7 * 24 * 60 * 60 // only show this message once per week
            );
        }

        if (!defined('PATH_CACHE')) {
            return false;
        }

        $tmpFolder = PATH_CACHE . 'tmp';

        // make sure PATH_CACHE/tmp exists
        if (!file_exists($tmpFolder)) {
            $created = mkdir($tmpFolder);
            if (!$created) {
                return false;
            }
        }

        // Create a temp file with prefix of 'ee' and get a file handler
        // with the same permissions as tmpfile()
        $path = tempnam($tmpFolder, 'ee');
        $tmpfile = ($path !== false) ? fopen($path, "w+b") : false;

        // Register a single shutdown function to remove any temporary files created during the request
        if (!static::$fallbackRegistered) {
            register_shutdown_function(function () use ($tmpFolder) {
                $files = glob("$tmpFolder/ee*") ?: [];
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            });
            static::$fallbackRegistered = true;
        }

        return $tmpfile;
    }
}
