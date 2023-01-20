<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FilePicker\Service\FilePicker;

use ExpressionEngine\Service\URL\URLFactory;
use ExpressionEngine\Model\File\File;

/**
 * FilePicker Service
 */
class FilePicker
{
    const CONTROLLER = 'addons/settings/filepicker/modal';

    protected $url;
    protected $active;
    protected $image_id;
    protected $directories = 'all';

    public function __construct(URLFactory $url)
    {
        $this->url = $url;
    }

    /**
     * Set the allowed directories
     *
     * @param String $dirs Allowed directories
     * @return FilePicker
     */
    public function setDirectories($dirs)
    {
        $this->directories = $dirs;

        return $this;
    }

    /**
     * Get a CP\URL instance that points to the filepicker endpoint
     *
     * @return CP\URL
     */
    public function getUrl()
    {
        $qs = array('requested_directory' => $this->directories);

        if (is_numeric($this->directories)) {
            $qs['field_upload_locations'] = $this->directories;
        } else {
            $qs['field_upload_locations'] = 'all';
        }

        return $this->url->make(static::CONTROLLER, $qs);
    }

    /**
     * Get a new Link instance
     *
     * @param String $text The link text [optional]
     * @return Link
     */
    public function getLink($text = null)
    {
        $link = new Link($this);

        if (isset($text)) {
            $link->setText($text);
        }

        return $link;
    }
}

// EOF
