<?php

namespace ExpressionEngine\Addons\Wygwam\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Wygwam Config Model class
 *
 * @package   Wygwam
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) Copyright (c) 2016 EEHarbor
 */

class Config extends Model
{
    protected static $_primary_key = 'config_id';
    protected static $_table_name = 'wygwam_configs';

    protected static $_typed_columns = array(
        'settings' => 'base64Serialized',
    );

    protected $config_id;
    protected $config_name;
    protected $settings;
}
