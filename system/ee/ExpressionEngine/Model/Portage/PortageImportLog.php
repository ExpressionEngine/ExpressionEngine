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
 * Portage Import Log Model
 */
class PortageImportLog extends Model
{
    protected static $_primary_key = 'log_id';
    protected static $_table_name = 'portage_import_logs';

    protected static $_typed_columns = array(
        'model_prev_state' => 'json'
    );

    protected static $_relationships = array(
        'PortageImport' => array(
            'type' => 'belongsTo'
        )
    );

    protected $log_id;
    protected $import_id;
    protected $portage_action;
    protected $model_name;
    protected $model_uuid;
    protected $model_prev_state;
}

// EOF
