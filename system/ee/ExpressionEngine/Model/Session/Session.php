<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Session;

use ExpressionEngine\Service\Model\Model;

/**
 * Session Model
 */
class Session extends Model
{
    protected static $_primary_key = 'session_id';
    protected static $_table_name = 'sessions';

    protected static $_typed_columns = array(
        'can_debug' => 'boolString',
        'pro_banner_seen' => 'boolString',
    );

    protected static $_relationships = array(
        'Member' => array(
            'type' => 'BelongsTo'
        )
    );

    protected $session_id;
    protected $member_id;
    protected $admin_sess;
    protected $ip_address;
    protected $user_agent;
    protected $login_state;
    protected $fingerprint;
    protected $sess_start;
    protected $auth_timeout;
    protected $last_activity;
    protected $can_debug;
    protected $mfa_flag;
    protected $pro_banner_seen;

    /**
     * Manage sudo-like timeout for "trust but verify" actions
     */
    const AUTH_TIMEOUT = '+15 minutes';

    public function resetAuthTimeout()
    {
        $this->setProperty('auth_timeout', ee()->localize->string_to_timestamp(self::AUTH_TIMEOUT));
        $this->save();
    }

    public function isWithinAuthTimeout()
    {
        return $this->auth_timeout > ee()->localize->now;
    }

    public function proBannerSeen()
    {
        return $this->getProperty('pro_banner_seen');
    }

    public function setProBannerSeen()
    {
        $this->setProperty('pro_banner_seen', 'y');
        $this->save();
    }
}

// EOF
