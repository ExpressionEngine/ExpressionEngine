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
 * Reset Password Model
 */
class ResetPassword extends Model
{
    protected static $_primary_key = 'reset_id';
    protected static $_table_name = 'reset_password';

    protected static $_relationships = array(
        'Member' => array(
            'type' => 'belongsTo'
        )
    );

    protected $reset_id;
    protected $member_id;
    protected $resetcode;
    protected $date;
}

// EOF
