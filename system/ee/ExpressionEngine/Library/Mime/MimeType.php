<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Mime;

use Exception;
use InvalidArgumentException;
use ExpressionEngine\Dependency\League\MimeTypeDetection;

/**
 * Mime Type
 */
class MimeType
{
    protected $whitelist = array();
    protected $kinds = array();
    protected $images = array();
    protected $detector;

    /**
     * Constructor
     *
     * @param array $mimes An array of MIME types to add to the whitelist
     * @return void
     */
    public function __construct(array $mimes = array())
    {
        $this->detector = new MimeTypeDetection\FinfoMimeTypeDetector();
        $this->addMimeTypes($mimes);
    }

    public function whitelistMimesFromConfig()
    {
        $whitelist = ee()->config->loadFile('mimes');

        $this->addMimeTypes($whitelist);

        // Add any mime types from the config
        $extra_mimes = ee()->config->item('mime_whitelist_additions');
        if ($extra_mimes !== false) {
            if (is_array($extra_mimes)) {
                $this->addMimeTypes($extra_mimes);
            } else {
                $this->addMimeTypes(explode('|', $extra_mimes));
            }
        }
    }

    /**
     * Adds a mime type to the whitelist
     *
     * @param string $mime The mime type to add to the whitelist
     * @return void
     */
    public function addMimeType($mime, $kind = null)
    {
        if (! is_string($mime)) {
            throw new InvalidArgumentException("addMimeType only accepts strings; " . gettype($mime) . " found instead.");
        }

        if (count(explode('/', $mime)) != 2) {
            throw new InvalidArgumentException($mime . " is not a valid MIME type.");
        }

        if (! in_array($mime, $this->whitelist)) {
            $this->whitelist[] = $mime;

            if (! empty($kind)) {
                if (! isset($this->kinds[$kind])) {
                    $this->kinds[$kind] = [];
                }
                $this->kinds[$kind][] = $mime;
            }

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
    public function addMimeTypes(array $mimes, $kind = null)
    {
        foreach ($mimes as $group => $mime) {
            if (is_array($mime)) {
                $this->addMimeTypes($mime, $group);
            } else {
                $this->addMimeType($mime, $kind);
            }
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
     * Checks whether mime belongs to one of supported types
     *
     * @param [type] $mime
     * @param [type] $mimeTypeKind
     * @return boolean
     */
    public function isOfKind($mime, $mimeTypeKind)
    {
        return (isset($this->kinds[$mimeTypeKind]) && in_array($mime, $this->kinds[$mimeTypeKind]));
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

        $mime = $this->detector->detectMimeTypeFromFile($path);
        $file_opening = null;

        if (is_null($mime)) {
            $file_opening = file_get_contents($path, false, null, 0, 50); //get first 50 bytes off the file
            $mime = $this->detector->detectMimeType($path, $file_opening);
        }

        // A few files are identified as plain text, which while true is not as
        // helpful as which type of plain text files they are.
        if ($mime == 'text/plain') {
            $detectorByExtension = new MimeTypeDetection\ExtensionMimeTypeDetector();
            $mimeByExtension = $detectorByExtension->detectMimeTypeFromFile($path);
            if (!empty($mimeByExtension)) {
                $mime = $mimeByExtension;
            }
        }

        // Set a default
        $mime = !is_null($mime) ? $mime :  'application/octet-stream';

        // try another method to get mime
        if ($mime == 'application/octet-stream') {
            $file_opening = ($file_opening) ?: file_get_contents($path, false, null, 0, 50);
            $mime = $this->guessOctetStream($file_opening);
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
        $default = 'application/octet-stream';

        if (is_array($buffer) || is_object($buffer)) {
            return $default;
        }

        $mime = $this->detector->detectMimeTypeFromBuffer((string) $buffer) ?: $default;

        // try another method to get mime
        if ($mime == 'application/octet-stream') {
            $mime = $this->guessOctetStream((string) $buffer);
        }

        return $mime;
    }

    public function guessOctetStream($contents)
    {
        $mime = 'application/octet-stream';

        if (strpos($contents, 'RIFF') === 0 && strpos($contents, 'WEBPVP8') !== false) {
            $mime = 'image/webp';
            // PDF files start with "%PDF" (25 50 44 46) or " %PDF"
            // @see https://en.wikipedia.org/wiki/Magic_number_%28programming%29#Examples
        } elseif (strpos($contents, '%PDF') !== false) {
            $mime = 'application/pdf';
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

        if (strpos($mime, 'image/svg') === 0) {
            $file = file_get_contents($path);
            if ((strpos($file, '<?xml') !== 0 && strpos($file, '<svg') !== 0) || strpos($file, '<svg') === false) {
                return false;
            }
            return true;
        }

        // If the reported mime-type is an icon, we won't do the next validation step because imagecreatefromstring does not support .ico files
        if ($mime === 'image/vnd.microsoft.icon' || $mime == 'image/x-icon') {
            try {
                $file = fopen($path, 'r');
                $first = fread($file, 4);
                fclose($file);
                return $first === "\x00\x00\x01\x00";
            } catch (\Exception $e) {
                if (DEBUG) {
                    show_error($e->getMessage());
                }
                return false;
            }
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
        if ($this->memberExcludedFromWhitelistRestrictions()) {
            return true;
        }
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
        if ($this->memberExcludedFromWhitelistRestrictions()) {
            return true;
        }
        return in_array($mime, $this->whitelist, true);
    }

    /**
     * Checks the config for specific member exceptions or member group
     * exceptions and compares the current member to those lists.
     *
     * @return bool TRUE if excluded; FALSE otherwise
     */
    protected function memberExcludedFromWhitelistRestrictions()
    {
        $excluded_members = ee()->config->item('mime_whitelist_member_exception');
        if ($excluded_members !== false) {
            $excluded_members = preg_split('/[\s|,]/', $excluded_members, -1, PREG_SPLIT_NO_EMPTY);
            $excluded_members = is_array($excluded_members) ? $excluded_members : array($excluded_members);

            if (in_array(ee()->session->userdata('member_id'), $excluded_members)) {
                return true;
            }
        }

        $excluded_member_groups = ee()->config->item('mime_whitelist_member_group_exception');
        if ($excluded_member_groups !== false) {
            $excluded_member_groups = preg_split('/[\s|,]/', $excluded_member_groups, -1, PREG_SPLIT_NO_EMPTY);
            $excluded_member_groups = is_array($excluded_member_groups) ? $excluded_member_groups : array($excluded_member_groups);

            if (ee('Permission')->hasAnyRole($excluded_member_groups)) {
                return true;
            }
        }

        return false;
    }
}

// EOF
