<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\File;

/**
 * File Model
 *
 * A model representing one of many possible upload destintations to which
 * files may be uploaded through the file manager or from the publish page.
 * Contains settings for this upload destination which describe what type of
 * files may be uploaded to it, as well as essential information, such as the
 * server paths where those files actually end up.
 */
class File extends FileSystemEntity
{
    protected static $_events = array(
        'beforeDelete',
        'beforeInsert'
    );

    public function onBeforeInsert()
    {
        // file_type is set based on mime_type on initial upload
        // and cannot be changed
        $mimes = ee()->config->loadFile('mimes');
        $fileTypes = array_filter(array_keys($mimes), 'is_string');
        foreach ($fileTypes as $fileType) {
            if (in_array($this->getProperty('mime_type'), $mimes[$fileType])) {
                $this->setProperty('file_type', $fileType);
                break;
            }
        }
    }
}

// EOF
