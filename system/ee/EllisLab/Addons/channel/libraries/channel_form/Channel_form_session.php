<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
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
	 * @access	public
	 * @param	mixed $config
	 * @return	void
	 */
	public function __construct($config)
	{
		$this->session_object = (isset($config['session_object'])) ? $config['session_object'] : NULL;

		$this->logged_out_member_id = (isset($config['logged_out_member_id'])) ? $config['logged_out_member_id'] : NULL;

		$this->logged_out_group_id = (isset($config['logged_out_group_id'])) ? $config['logged_out_group_id'] : NULL;

		if (is_object($this->session_object))
		{
			foreach (get_object_vars($this->session_object) as $key => $value)
			{
				$this->{$key} = $value;
			}
		}

		if ($this->logged_out_member_id)
		{
			$this->userdata['member_id'] = $this->logged_out_member_id;
		}

		if ($this->logged_out_group_id)
		{
			$this->userdata['group_id'] = $this->logged_out_group_id;
		}

		$this->userdata['assigned_channels'] = array();

		ee()->db->select('exp_channels.channel_id, exp_channels.channel_title');
		ee()->db->where('exp_channels.site_id', ee()->config->item('site_id'));
		ee()->db->order_by('exp_channels.channel_title');

		if ($this->userdata('group_id') != 1)
		{
			ee()->db->join('exp_channel_member_groups', 'exp_channel_member_groups.channel_id = exp_channels.channel_id');
			ee()->db->where('exp_channel_member_groups.group_id', $this->userdata('group_id'));
		}

		$query = ee()->db->get('exp_channels');

		foreach ($query->result() as $row)
		{
			$this->userdata['assigned_channels'][$row->channel_id] = $row->channel_title;
		}
	}

	/**
	 * userdata
	 *
	 * @param	mixed $str
	 * @return	void
	 */
	public function userdata($str)
	{
		if ($str == 'member_id' && $this->logged_out_member_id)
		{
			return $this->logged_out_member_id;
		}
		elseif ($str == 'group_id' && $this->logged_out_group_id)
		{
			return $this->logged_out_group_id;
		}

		return $this->session_object->userdata($str);
	}
}

// EOF
