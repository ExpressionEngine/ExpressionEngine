<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SC_Session extends EE_Session
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
	 
		$this->EE->db->select('exp_channels.channel_id, exp_channels.channel_title');
		$this->EE->db->where('exp_channels.site_id', $this->EE->config->item('site_id'));
		$this->EE->db->order_by('exp_channels.channel_title');
			
		if ($this->userdata('group_id') != 1)
		{
			$this->EE->db->join('exp_channel_member_groups', 'exp_channel_member_groups.channel_id = exp_channels.channel_id');
			$this->EE->db->where('exp_channel_member_groups.group_id', $this->userdata('group_id'));
		}
		
		$query = $this->EE->db->get('exp_channels');
		
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
		switch($str)
		{
			case ($str == 'member_id' && $this->logged_out_member_id):
				return $this->logged_out_member_id;
			case ($str == 'group_id' && $this->logged_out_group_id):
				return $this->logged_out_group_id;
			default:
				return $this->session_object->userdata($str);
		}
	}
}

/* End of file SC_Session.php */
/* Location: ./system/expressionengine/modules/safecracker/library/SC_Session.php */