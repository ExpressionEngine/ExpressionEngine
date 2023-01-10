<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Model\Dock;

use ExpressionEngine\Service\Model\Model;

/**
 * Dock Model
 */
class Dock extends Model
{
    protected static $_primary_key = 'dock_id';
    protected static $_table_name = 'docks';

    protected static $_relationships = array(
        'Prolets' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'pro:Prolet',
            'pivot' => array(
                'table' => 'dock_prolets',
                'left' => 'dock_id',
                'right' => 'prolet_id'
            )
        )
    );

    protected $dock_id;
    protected $site_id; //is not really used ATM, but required for model to get saved
}
