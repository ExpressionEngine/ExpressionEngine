<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Form Session Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		$this->EE =& get_instance();
		
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

	// --------------------------------------------------------------------
	
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

/* End of file Channel_form_session.php */
/* Location: ./system/expressionengine/modules/channel/library/Channel_form_session.php */