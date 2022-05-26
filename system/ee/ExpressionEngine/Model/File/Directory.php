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
 * Directory Model
 *
 */
class Directory extends FileSystemEntity
{
    protected static $_events = array(
        'beforeInsert'
    );

    public function onBeforeInsert()
    {
        $this->setProperty('model_type', 'Directory');
        $this->setProperty('upload_date', ee()->localize->now);
        $this->setProperty('modified_date', ee()->localize->now);
        $this->setProperty('uploaded_by_member_id', ee()->session->userdata('member_id'));
        $this->setProperty('modified_by_member_id', ee()->session->userdata('member_id'));
        $this->setProperty('title', $this->getProperty('file_name'));
    }
}

// EOF
