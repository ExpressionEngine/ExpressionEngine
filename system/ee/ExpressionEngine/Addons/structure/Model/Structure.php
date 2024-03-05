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
 * ExpressionEngine Structure Model
 */
class Structure extends Model
{
    protected static $_primary_key = 'entry_id';
    protected static $_table_name = 'structure';

    protected static $_typed_columns = array(
        'site_id' => 'int',
        'entry_id' => 'int',
        'parent_id' => 'int',
        'channel_id' => 'int',
        'listing_cid' => 'int',
        'lft' => 'int',
        'rgt' => 'int',
        'hidden' => 'boolString',
        'template_id' => 'int',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Site',
            'inverse' => array(
                'name' => 'Structure',
                'type' => 'hasOne'
            )
        ),
        'Channel' => array(
            'type' => 'belongsTo',
            'model' => 'ee:Channel',
            'inverse' => array(
                'name' => 'Structure',
                'type' => 'hasOne'
            )
        ),
        'ChannelEntry' => array(
            'type' => 'belongsTo',
            'model' => 'ee:ChannelEntry',
            'inverse' => array(
                'name' => 'Structure',
                'type' => 'hasOne'
            )
        )
    );

    protected $site_id;
    protected $entry_id;
    protected $parent_id;
    protected $channel_id;
    protected $listing_cid;
    protected $lft;
    protected $rgt;
    protected $dead;
    protected $hidden;
    protected $structure_url_title;
    protected $structure_uri; // full
    protected $template_id;
    protected $updated;

}