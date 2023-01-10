<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Form Session Class
 */
class Channel_form_session extends EE_Session
{
    public $logged_out_member_id;
    public $logged_out_group_id;
    public $session_object;

    /**
     * SC_Session
     *
     * @access public
     * @param mixed $config
     * @return void
     */
    public function __construct($config)
    {
        $this->session_object = (isset($config['session_object'])) ? $config['session_object'] : null;

        $this->logged_out_member_id = (isset($config['logged_out_member_id'])) ? $config['logged_out_member_id'] : null;

        $this->logged_out_group_id = (isset($config['logged_out_group_id'])) ? $config['logged_out_group_id'] : null;

        if (is_object($this->session_object)) {
            foreach (get_object_vars($this->session_object) as $key => $value) {
                $this->{$key} = $value;
            }
        }

        if ($this->logged_out_member_id) {
            $this->userdata['member_id'] = $this->logged_out_member_id;
        }

        if ($this->logged_out_group_id) {
            $this->userdata['group_id'] = $this->logged_out_group_id;
        }

        $this->userdata['assigned_channels'] = ee()->session->getMember()->getAssignedChannels()->getDictionary('channel_id', 'channel_title');
    }

    /**
     * userdata
     *
     * @param mixed $str
     * @return void
     */
    public function userdata($str, $default = false)
    {
        if ($str == 'member_id' && $this->logged_out_member_id) {
            return $this->logged_out_member_id;
        } elseif ($str == 'group_id' && $this->logged_out_group_id) {
            return $this->logged_out_group_id;
        }

        return $this->session_object->userdata($str);
    }
}

// EOF
