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

    protected static $_validation_rules = array(
        'title' => 'xss',
        'description' => 'xss',
        'credit' => 'xss',
        'location' => 'xss',
        'file_name' => 'required|xss|alphaDash|unique[upload_location_id,directory_id]'
    );

    public function onBeforeInsert()
    {
        $this->setProperty('model_type', 'Directory');
        $this->setProperty('upload_date', ee()->localize->now);
        $this->setProperty('modified_date', ee()->localize->now);
        $this->setProperty('uploaded_by_member_id', ee()->session->userdata('member_id'));
        $this->setProperty('modified_by_member_id', ee()->session->userdata('member_id'));
        $this->setProperty('title', $this->getProperty('file_name'));
        $this->setProperty('mime_type', 'directory');
    }

    public function get__file_hw_original()
    {
        return null;
    }

    public function geSubdirectoryTree()
    {
        $tree = [];
        $directories = ee('Model')->get('Directory')
            ->filter('directory_id', $this->file_id)
            ->filter('model_type', 'Directory')
            ->all();

        foreach ($directories as $directory) {
            $tree[$directory->file_name] = [
                'id' => $directory->file_id,
                'name' => $directory->file_name,
                'subdirectories' => $directory->geSubdirectoryTree()
            ];
        }

        return $tree;
    }
}

// EOF
