<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Security;

use ExpressionEngine\Service\Model\Model;

/**
 * Captcha Model
 */
class Captcha extends Model
{
    protected static $_primary_key = 'captcha_id';
    protected static $_table_name = 'captcha';

    protected static $_validation_rules = array(
        'ip_address' => 'ip_address'
    );

    protected $captcha_id;
    protected $date;
    protected $ip_address;
    protected $word;
}

// EOF
