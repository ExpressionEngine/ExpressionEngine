<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * SpamTrap Model
 */
class SpamTrap extends Model
{
    protected static $_table_name = 'spam_trap';
    protected static $_primary_key = 'trap_id';

    protected static $_typed_columns = array(
        'entity' => 'serialized',
        'optional_data' => 'serialized',
        'trap_date' => 'timestamp',
    );

    protected static $_relationships = array(
        'Author' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Member',
            'from_key' => 'author_id',
            'weak' => true,
            'inverse' => array(
                'name' => 'trap_id',
                'type' => 'hasMany'
            )
        )
    );

    protected $author_id;
    protected $content_type;
    protected $document;
    protected $entity;
    protected $ip_address;
    protected $optional_data;
    protected $site_id;
    protected $trap_date;
    protected $trap_id;
}

// EOF
