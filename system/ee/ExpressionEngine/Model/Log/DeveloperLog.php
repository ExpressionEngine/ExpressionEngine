<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Log;

use ExpressionEngine\Service\Model\Model;

/**
 * Developer Log Model
 */
class DeveloperLog extends Model
{
    protected static $_primary_key = 'log_id';
    protected static $_table_name = 'developer_log';

    protected static $_relationships = array(
        'Template' => array(
            'type' => 'belongsTo'
        )
    );

    protected $log_id;
    protected $timestamp;
    protected $viewed;
    protected $description;
    protected $function;
    protected $line;
    protected $file;
    protected $deprecated_since;
    protected $use_instead;
    protected $template_id;
    protected $template_name;
    protected $template_group;
    protected $addon_module;
    protected $addon_method;
    protected $snippets;
    protected $hash;
}

// EOF
