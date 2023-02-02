<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\File;

/**
 * Directory Model
 *
 */
class Directory extends FileSystemEntity
{
    protected static $_validation_rules = array(
        'title' => 'xss',
        'description' => 'xss',
        'credit' => 'xss',
        'location' => 'xss',
        'file_name' => 'required|xss|alphaDash|notStartsWith[_]|unique[upload_location_id,directory_id]'
    );

    public function onBeforeInsert()
    {
        parent::onBeforeInsert();
        $this->setProperty('model_type', 'Directory');
        $this->setProperty('title', $this->getProperty('file_name'));
        $this->setProperty('mime_type', 'directory');
        $this->setProperty('file_type', 'directory');
    }

    public function get__file_hw_original()
    {
        return null;
    }
}

// EOF
