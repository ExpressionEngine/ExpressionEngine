<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Model\Config;

use ExpressionEngine\Service\Model\Model;

/**
 * Config Model
 */
class Config extends Model
{
    protected static $_primary_key = 'config_id';
    protected static $_table_name = 'config';

    protected static $_typed_columns = [
        'config_id' => 'int',
        'site_id' => 'int',
    ];

    protected static $_relationships = [
        'Site' => [
            'type' => 'belongsTo'
        ],
    ];

    protected static $_validation_rules = [
        'config_id' => 'required',
        'key' => 'required',
    ];

    // protected static $_events = [];

    // Properties
    protected $config_id;
    protected $site_id;
    protected $key;
    protected $value;

    public function set__value($value)
    {
        // exception for email_newline, which uses backslashes, and is not a path variable
        if ($this->key != 'email_newline') {
            $value = str_replace('\\', '/', (string) $value);
        }

        $this->setRawProperty('value', $value);
    }

    public function get__parsed_value()
    {
        return parse_config_variables($this->getRawProperty('value'));
    }
}

// EOF
