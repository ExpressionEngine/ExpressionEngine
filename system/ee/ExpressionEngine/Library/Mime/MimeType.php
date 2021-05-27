<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Mime;

use Exception;
use InvalidArgumentException;

/**
 * Mime Type
 */
class MimeType
{
    protected $whitelist = array();
    protected $images = array();

    /**
     * Constructor
     *
     * @param array $mimes An array of MIME types to add to the whitelist
     * @return void
     */
    public function __construct(array $mimes = array())
    {
        $this->addMimeTypes($mimes);
    }

    /**
     * Adds a mime type to the whitelist
     *
     * @param string $mime The mime type to add to the whitelist
     * @return void
     */
    public function addMimeType($mime)
    {
        if (! is_string($mime)) {
            throw new InvalidArgumentException("addMimeType only accepts strings; " . gettype($mime) . " found instead.");
        }

        if (count(explode('/', $mime)) != 2) {
            throw new InvalidArgumentException($mime . " is not a valid MIME type.");
        }

        if (! in_array($mime, $this->whitelist)) {
            $this->whitelist[] = $mime;

            if (strpos($mime, 'image/') === 0) {
                $this->images[] = $mime;
            }
        }
    }

    /**
     * Adds multiple mime types to the whitelist
     *
     * @param array $mimes An array of MIME types to add to the whitelist
     * @return void
     */
    public function addMimeTypes(array $mimes)
    {
        foreach ($mimes as $mime) {
            $this->addMimeType($mime);
        }
    }

    /**
     * Returns the whitelist of MIME Types
     *
     * @return array An array of MIME types that are on the whitelist
     */
    public function getWhitelist()
    {
        return $this->whitelist;
    }

    /**
     * Determines the MIME type of a file
     *
     * @throws Exception If the file does not exist
     * @param string $path The full path to the file being checked
     * @return string The MIME type of the file
     */
    public function ofFile($path)
    {
        if (! file_exists($path)) {
            throw new Exception("File " . $path . " does not exist.");
        }

        // Set a default
        $mime = 'application/octet-stream';

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $fres = @finfo_file($finfo, $path);
            if (($fres !== false)
                && is_string($fres)
                && (strlen($fres) > 0)) {
                $mime = $fres;
            }

            @finfo_close($finfo);
        }

        //try another method to get mime
        if ($mime == 'application/octet-stream') {
            $file_opening = file_get_contents($path, false, null, 0, 50);//get first 50 bytes off the file
            if (strpos($file_opening, 'RIFF' === 0) && strpos($file_opening, 'WEBPVP8' !== false)) {
                $mime = 'image/webp';
            }
        }

        // A few files are identified as plain text, which while true is not as
        // helpful as which type of plain text files they are.
        if ($mime == 'text/plain') {
            $parts = explode('.', $path);
            $extension = end($parts);

            switch ($extension) {
                case 'css':
                    $mime = 'text/css';

                    break;

                case 'js':
                    $mime = 'application/javascript';

                    break;

                case 'json':
                    $mime = 'application/json';

                    break;

                case 'svg':
                    $mime = 'image/svg+xml';

                    break;
            }
        }

        return $mime;
    }

    /**
     * Determines the MIME type of a buffer
     *
     * @param string $buffer The buffer/data to check
     * @return string The MIME type of the buffer
     */
    public function ofBuffer($buffer)
    {
        // Set a default
        $mime = 'application/octet-stream';

        $finfo = @finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo !== false && !is_array($buffer) && !is_object($buffer)) {
            $fres = @finfo_buffer($finfo, $buffer);
            if (($fres !== false)
                && is_string($fres)
                && (strlen($fres) > 0)) {
                $mime = $fres;
            }

            @finfo_close($finfo);
        }

        return $mime;
    }

    /**
     * Determines if a file is an image or not.
     *
     * @throws Exception If the file does not exist
     * @param string $path The full path to the file being checked
     * @return bool TRUE if it is an image; FALSE if not
     */
    public function fileIsImage($path)
    {
        $mime = $this->ofFile($path);
        if (! $this->isImage($mime)) {
            return false;
        }

        if ($mime == 'image/svg+xml') {
            $file = file_get_contents($path);
            if (strpos($file, '<?xml') !==0 || strpos($file, '<svg') === false) {
                return false;
            }
            return true;
        }

        // If the reported mime-type is an image we'll do an extra validation
        // step and try to create an image from the data.
        try {
            ee('Memory')->setMemoryForImageManipulation($path, 1.9);
            $im = @imagecreatefromstring(file_get_contents($path));

            return $im !== false;
        } catch (\Exception $e) {
            if (DEBUG) {
                show_error($e->getMessage());
            }

            return false;
        }
    }

    /**
     * Determines if a MIME type is in our list of valid image MIME types.
     *
     * @param string $mime The mime to check
     * @return bool TRUE if it is an image; FALSE if not
     */
    public function isImage($mime)
    {
        return in_array($mime, $this->images, true);
    }

    /**
     * Gets the MIME type of a file and compares it to our whitelist to see if
     * it is safe for upload.
     *
     * @throws Exception If the file does not exist
     * @param string $path The full path to the file being checked
     * @return bool TRUE if it safe; FALSE if not
     */
    public function fileIsSafeForUpload($path)
    {
        return $this->isSafeForUpload($this->ofFile($path));
    }

    /**
     * Checks a given MIME type against our whitelist to see if it is safe for
     * upload
     *
     * @param string $mime The mime to check
     * @return bool TRUE if it is an image; FALSE if not
     */
    public function isSafeForUpload($mime)
    {
        return in_array($mime, $this->whitelist, true);
    }
}

// EOF
