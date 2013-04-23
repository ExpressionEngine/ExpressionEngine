<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Menu Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Accessories {

	var $ignored_controllers = array('css.php', 'javascript.php', 'login.php', 'search.php', 'index.html');
	var $ignored_member_groups = array(2, 3, 4);

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		ee()->load->library('addons');
	}	

	// --------------------------------------------------------------------	

	/**
	 * Generate Accessories
	 * 
	 * Builds the Accessories tabs and content menu
	 *
	 * @access	public
	 * @return	void
	 */	 
	function generate_accessories($permissions = '')
	{
		$accessories = array();
		$ext_len = strlen('.php');
		
		$controller	= ee()->router->fetch_class();
		$member_group = ee()->session->userdata('group_id');

		$files = ee()->addons->get_files('accessories');
		$installed = ee()->addons->get_installed('accessories');

		foreach ($files as $name => $info)
		{						
			if (isset($installed[$name]))
			{
				$valid_controller = FALSE;
				$valid_group = FALSE;
				
				$c = explode('|', $installed[$name]['controllers']);
				$g = explode('|', $installed[$name]['member_groups']);

				// Make them all arrays
				$c = is_array($c) ? $c : array($c);
				$g = is_array($g) ? $g : array($g);
				
				// Filter out the blanks
				$c = (current($c) == '') ? array() : $c;
				$g = (current($g) == '') ? array() : $g;

				// Check for valid controllers
				if (count($c) > 0)
				{
					$valid_controller = in_array($controller, $c);
				}
				
				if (count($g) > 0)
				{
					$valid_group = in_array($member_group, $g);
				}
				
				$installed[$name]['controller'] = $c;
				$installed[$name]['member_groups'] = $g;

				if ($valid_controller && $valid_group)
				{
					@include_once($info['path'].$info['file']);

					if (class_exists($info['class']))
					{
						$third_party = FALSE;

						if (array_key_exists('package', $info))
						{
							$third_party = TRUE;
							ee()->load->add_package_path(PATH_THIRD.strtolower($name).'/');
						}
						
						$obj = new $info['class']();

						// Update Accessory First? Check if an update() function is present, then versions
						if (method_exists($obj, 'update') === TRUE AND $installed[$name]['accessory_version'] < $obj->version)
						{
							if ($obj->update() !== FALSE)
							{
								// Its up to the developer to return FALSE on failure, otherwise we'll assume it succeeded.
								ee()->load->model('addons_model');
								ee()->addons_model->update_accessory($info['class'], array('accessory_version'=>$obj->version));
							}
						}

						$obj->set_sections();
						$accessories[] = $obj;
						unset($obj);
						
						if ($third_party === TRUE)
						{
							ee()->load->remove_package_path(PATH_THIRD.strtolower($name).'/');
						}
					}
					else
					{
						log_message('error', "Invalid Accessory class: {$info['class']}");
					}
				}
			}
		}

		return $accessories;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Accessory by Name
	 *
	 * Returns a path to an accessory for the supplied name
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function fetch_path_by_name($name)
	{
		$files = ee()->addons->get_files('accessories');

		if (isset($files[$name]))
		{
			return $files[$name]['path'].$files[$name]['file'];
		}
		else
		{
			return FALSE;
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Update the Accessory Display Settings
	 *
	 * Returns TRUE / FALSE if the update was (un)successful.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function update_placement($acc_name, $member_groups = FALSE, $controllers = FALSE)
	{
		if ($member_groups === FALSE && $controllers === FALSE)
		{
			ee()->load->model('member_model');
			
			// all member groups by default
			$member_groups = array();
			$member_groups_query = ee()->member_model->get_member_groups();

			foreach ($member_groups_query->result() as $group)
			{
				if ( ! in_array($group->group_id, $this->ignored_member_groups))
				{
					$member_groups[] = $group->group_id;
				}
			}

			// all controllers by default
			$controllers = array();

			foreach(directory_map(APPPATH.'controllers/cp') as $file)
			{
				if (in_array($file, $this->ignored_controllers))
				{
					continue;
				}

				$file = str_replace('.php', '', $file);
				$controllers[] = str_replace('.php', '', $file);
			}
		}
		
		$data = array('member_groups' => '', 'controllers' => '');
		
		if (is_array($member_groups))
		{
			$data['member_groups'] = implode('|', $member_groups);
		}
		
		if (is_array($controllers))
		{
			$data['controllers'] = implode('|', $controllers);
		}

		ee()->load->model('addons_model');
		ee()->addons_model->update_accessory($acc_name, $data);

		return (ee()->db->affected_rows() > 0) ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Accessory Class
	 *
	 * Checks for an accessory and returns the prepped class name
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	function _get_accessory_class($name)
	{
		// make sure the accessory exists
		if (($path = $this->fetch_path_by_name($name)) === FALSE)
		{
			return FALSE;
		}
		
		@include_once($path);
		$class = ucfirst($name).'_acc';

		// make sure the class exists
		if ( ! class_exists($class))
		{
			return FALSE;
		}
		
		if (strncmp($path, PATH_THIRD, strlen(PATH_THIRD)) == 0)
		{
			ee()->load->add_package_path(PATH_THIRD.strtolower($name).'/', FALSE);
		}
		
		return $class;
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file Accessories.php */
/* Location: ./system/expressionengine/libraries/Accessories.php */