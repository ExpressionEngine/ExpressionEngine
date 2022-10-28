<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Portage;

use ExpressionEngine\Service\Model\Model;

/**
 * Portage Import Model
 */
class PortageImport extends Model
{
    protected static $_primary_key = 'import_id';
    protected static $_table_name = 'portage_imports';

    protected static $_typed_columns = array(
        'components' => 'json'
    );

    protected static $_relationships = array(
        'Member' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Member',
            'weak' => true
        ),
        'PortageImportLogs' => array(
            'type' => 'hasMany',
            'model' => 'ee:PortageImportLog',
            'weak' => true
        ),
    );

    protected $import_id;
    protected $import_date;
    protected $member_id;
    protected $uniqid;
    protected $version;
    protected $components;
}

// EOF
