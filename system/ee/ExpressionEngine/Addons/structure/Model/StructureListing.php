<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Structure\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine Structure Listing Model
 */
class StructureListing extends Model
{
    protected static $_primary_key = 'entry_id';
    protected static $_table_name = 'structure_listings';

    protected static $_typed_columns = array(
        'site_id' => 'int',
        'entry_id' => 'int',
        'parent_id' => 'int',
        'channel_id' => 'int',
        'template_id' => 'int',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Site',
            'inverse' => array(
                'name' => 'StructureListing',
                'type' => 'hasOne'
            )
        ),
        'Channel' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Channel',
            'inverse' => array(
                'name' => 'StructureListing',
                'type' => 'hasOne'
            )
        ),
        'ChannelEntry' => array(
            'type' => 'belongsTo',
            'model' => 'ee:ChannelEntry',
            'inverse' => array(
                'name' => 'StructureListing',
                'type' => 'hasOne'
            )
        )
    );

    protected $site_id;
    protected $entry_id;
    protected $parent_id;
    protected $channel_id;
    protected $template_id;
    protected $uri;
    protected $structure_uri; //full

}