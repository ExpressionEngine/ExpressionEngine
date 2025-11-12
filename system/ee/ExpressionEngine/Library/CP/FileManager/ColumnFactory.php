<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager;
use ExpressionEngine\Library\CP\EntryManager;

/**
 * File Manager Column Factory
 */
class ColumnFactory extends EntryManager\ColumnFactory
{
    protected static $standard_columns = [
        'file_id' => Columns\FileId::class,
        'title' => Columns\Title::class,
        'file_name' => Columns\FileName::class,
        'file_type' => Columns\FileType::class,
        'file_size' => Columns\FileSize::class,
        'description' => Columns\Description::class,
        'credit' => Columns\Credit::class,
        'location' => Columns\Location::class,
        'categories' => Columns\Categories::class,
        'upload_directory' => Columns\UploadDirectory::class,
        'upload_date' => Columns\UploadDate::class,
        'uploaded_by_member_id' => Columns\UploadAuthor::class,
        'modified_date' => Columns\ModifiedDate::class,
        'modified_by_member_id' => Columns\ModifyAuthor::class,
        'file_hw_original' => Columns\Dimensions::class,
        'checkbox' => Columns\Checkbox::class,
        'thumbnail' => Columns\Thumbnail::class,
        'manage' => Columns\Manage::class,
        'usage' => Columns\Usage::class,
    ];

    /**
     * Returns Column objects for all custom field columns
     *
     * @return array[Column]
     */
    protected static function getCustomFieldColumns($channel = false)
    {
        return [];
    }

    /**
     * Module tabs not supported
     *
     * @return array
     */
    protected static function getTabColumns()
    {
        return [];
    }
}
