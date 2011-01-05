<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Template Structure API Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Api_template_structure extends Api {
	
	/**
	 * @php4 -- All of the class properties are protected.
	 * When php4 support is deprecated, make them accessible via __get()
	 */
	var $template_info			= array();				// cache of previously fetched template info
	var $group_info				= array();				// cache of previously fetched group info
	var $reserved_names			= array('act', 'css');	// array of reserved template group names

	// file extensions used when saving templates as files
	var $file_extensions	= array(
										'webpage'	=> '.html',
										'static'	=> '.html',
										'feed'		=> '.feed',
										'css'		=> '.css',
										'js'		=> '.js',
										'xml'		=> '.xml'
									);
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
		
		$this->EE->load->model('template_model');
		
		// initialize the reserved names array
		$this->_load_reserved_groups();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get Template Group metadata
	 *
	 * @access	public
	 * @param	int
	 * @return	object
	 */
	function get_group_info($group_id)
	{
		if ($group_id == '')
		{
			$this->_set_error('channel_id_required');
			return FALSE;
		}
		
		// return cached query object if available
		if (isset($this->group_info[$group_id]))
		{
			return $this->group_info[$group_id];
		}
		
		$query = $this->EE->template_model->get_group_info($group_id);
		
		if ($query->num_rows() == 0)
		{
			$this->_set_error('invalid_group_id');
			return FALSE;
		}
		
		$this->group_info[$group_id] = $query;
		return $query;		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Create Group
	 *
	 * Creates a new template group
	 *
	 * @access	public
	 * @param	array
	 * @return	int
	 */
	function create_template_group($data, $duplicate = FALSE)
	{
		if ( ! is_array($data) OR count($data) == 0)
		{
			return FALSE;
		}
		
		$group_name	= '';
		
		// turn our array into variables
		extract($data);
		
		if ($site_id === NULL OR ! is_numeric($site_id))
		{
			$site_id = $this->config->item('site_id');
		}
		
		// validate group name
		if ($group_name == '')
		{
			$this->_set_error('group_required');
		}
		
		if ( ! $this->EE->api->is_url_safe($group_name))
		{
			$this->_set_error('illegal_characters');
		}			
		
		if (in_array($group_name, $this->reserved_names))
		{
			$this->_set_error('reserved_name');
		}
		
		// check if it's taken, too
		$count = $this->EE->super_model->count('template_groups', array('site_id' => $site_id, 'group_name' => $group_name));
		
		if ($count > 0)
		{
			$this->_set_error('template_group_taken');
		}

		// error trapping is all over, shall we continue?
		if ($this->error_count() > 0)
		{
			return FALSE;
		}

		$is_site_default = (isset($is_site_default) && $is_site_default == 'y') ? 'y' : 'n';
		
		$data = array();
		
		foreach (array('group_name', 'group_order', 'is_site_default', 'site_id') as $field)
		{
			if (isset(${$field}))
			{
				$data[$field] = ${$field};
			}
		}
		
		$group_id = $this->EE->template_model->create_group($data);
		
		$duplicate = FALSE;
					
		if (is_numeric($duplicate_group))
		{
			$fields = array('template_name', 'template_data', 'template_type', 'template_notes', 'cache', 'refresh', 'no_auth_bounce', 'allow_php', 'php_parse_location');
			$query = $this->EE->template_model->get_templates($site_id, $fields, array('group_id' => $duplicate_group));
		
			if ($query->num_rows() > 0)
			{
				$duplicate = TRUE;
			}
		}
		
		
		if ($duplicate !== TRUE)
		{
			// just create the default 'index' template
			$template_data = array(
									'group_id'	  	=> $group_id,
									'template_name' => 'index',
									'edit_date'		=> $this->EE->localize->now,
									'site_id'		=> $site_id
								 );

			$this->EE->template_model->create_template($template_data);			
		}
		else
		{				
			foreach ($query->result() as $row)
			{				
				$data = array(
								'group_id'				=> $group_id,
								'template_name'  		=> $row->template_name,
								'template_notes'  		=> $row->template_notes,
								'cache'  				=> $row->cache,
								'refresh'  				=> $row->refresh,
								'no_auth_bounce'  		=> $row->no_auth_bounce,
								'php_parse_location'	=> $row->php_parse_location,
								'allow_php'  			=> ($this->EE->session->userdata['group_id'] == 1) ? $row->allow_php : 'n',
								'template_type' 		=> $row->template_type,
								'template_data'  		=> $row->template_data,
								'edit_date'				=> $this->EE->localize->now,
								'site_id'				=> $this->EE->config->item('site_id')
							 );
				
				$this->EE->template_model->create_template($template_data);
			}
		}
		
		return $group_id;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load Reserved Groups
	 *
	 * Adds all potential reserved names to the reserved groups array
	 *
	 * @access	private
	 * @php4 -- Change to truly private when php support is deprecated.
	 * @return	void
	 */
	function _load_reserved_groups()
	{
		if ($this->EE->config->item("forum_is_installed") == 'y' && $this->EE->config->item("forum_trigger") != '')
		{
			$this->reserved_names[] = $this->EE->config->item("forum_trigger");
		}

		if ($this->EE->config->item("use_category_name") == 'y' && $this->EE->config->item("reserved_category_word") != '')
		{
			$this->reserved_names[] = $this->EE->config->item("reserved_category_word");
		}

		if ($this->EE->config->item("forum_is_installed") == 'y' && $this->EE->config->item("forum_trigger") != '')
		{
			$this->reserved_names[] = $this->EE->config->item("forum_trigger");
		}

		if ($this->EE->config->item("profile_trigger") != '')
		{
			$this->reserved_names[] = $this->EE->config->item("profile_trigger");
		}
	}

	// --------------------------------------------------------------------

	/**
	 * File Extensions
	 *
	 * Returns a file extension that corresponds to the template type
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function file_extensions($template_type)
	{
		return (isset($this->file_extensions[$template_type])) ? $this->file_extensions[$template_type] : '';
	}

	// --------------------------------------------------------------------	
}
// END CLASS

/* End of file Api_template_structure.php */
/* Location: ./system/expressionengine/libraries/api/Api_template_structure.php */