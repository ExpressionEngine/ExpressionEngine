<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Cookie;

use ExpressionEngine\Service\Model\Model;

/**
 * Cookie Settings Model
 */
class CookieSetting extends Model
{
    protected static $_primary_key = 'cookie_id';
    protected static $_table_name = 'cookie_settings';

    protected static $_validation_rules = array(
        'cookie_provider' => 'required|alphaDash',
        'cookie_name' => 'required|alphaDash',
        'cookie_title' => 'required|noHtml'
    );

    protected static $_typed_columns = [
        'cookie_id' => 'int',
        'cookie_provider' => 'string',
        'cookie_name' => 'string',
        'cookie_title' => 'string',
        'cookie_description' => 'string'
    ];

    protected static $_relationships = [
        'ConsentRequestVersion' => [
            'type' => 'hasAndBelongsToMany',
            'model' => 'ConsentRequestVersion',
            'pivot' => array(
                'table' => 'consent_request_version_cookies'
            )
        ],
    ];

    // protected static $_events = [];

    // Properties
    protected $cookie_id;
    protected $cookie_provider;
    protected $cookie_name;
    protected $cookie_lifetime;
    protected $cookie_enforced_lifetime;
    protected $cookie_title;
    protected $cookie_description;
}

// EOF
