<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\File\Gateway;

use ExpressionEngine\Service\Model\Gateway;

/**
 * Files Table
 */
class FileGateway extends Gateway
{
    protected static $_table_name = 'files';
    protected static $_primary_key = 'file_id';

    // Properties
    protected $file_id;
    protected $model_type;
    protected $site_id;
    protected $title;
    protected $upload_location_id;
    protected $directory_id;
    protected $mime_type;
    protected $file_type;
    protected $file_name;
    protected $file_size;
    protected $description;
    protected $credit;
    protected $location;
    protected $uploaded_by_member_id;
    protected $upload_date;
    protected $modified_by_member_id;
    protected $modified_date;
    protected $file_hw_original;
}

// EOF
