<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use  ExpressionEngine\Library\Mime\MimeType;

/**
 * Core Mime type
 */
class Mime_type
{
    /**
     * Determines the MIME type of a file
     *
     * @see MimeType::ofFile
     */
    public function ofFile($path)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0', "ee('MimeType')->ofFile()");
        return ee('MimeType')->ofFile($path);
    }

    /**
     * Determines the MIME type of a buffer
     *
     * @see MimeType::ofFile
     */
    public function ofBuffer($buffer)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0', "ee('MimeType')->ofBuffer()");
        return ee('MimeType')->ofBuffer($buffer);
    }

    /**
     * Determines if a file is an image or not.
     *
     * @see MimeType::fileIsImage
     */
    public function fileIsImage($path)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0', "ee('MimeType')->fileIsImage()");
        return ee('MimeType')->fileIsImage($path);
    }

    /**
     * Determines if a MIME type is in our list of valid image MIME types.
     *
     * @see MimeType::isImage
     */
    public function isImage($mime)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0', "ee('MimeType')->isImage()");
        return ee('MimeType')->isImage($mime);
    }

    /**
     * Gets the MIME type of a file and compares it to our whitelist to see if
     * it is safe for upload.
     *
     * @see MimeType::fileIsSafeForUpload
     */
    public function fileIsSafeForUpload($path)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0', "ee('MimeType')->fileIsSafeForUpload()");
        return ee('MimeType')->fileIsSafeForUpload($path);
    }

    /**
     * Checks a given MIME type against our whitelist to see if it is safe for
     * upload
     *
     * @see MimeType::isSafeForUpload
     */
    public function isSafeForUpload($mime)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0', "ee('MimeType')->isSafeForUpload()");
        return ee('MimeType')->isSafeForUpload($mime);
    }

    /**
     * Returns the whitelist of MIME Types
     *
     * @return array An array of MIME types that are on the whitelist
     */
    public function getWhitelist()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('7.0.0', "ee('MimeType')->getWhitelist()");
        return ee('MimeType')->getWhitelist();
    }
}

// EOF
