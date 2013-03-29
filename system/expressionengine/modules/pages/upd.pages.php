<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

// --------------------------------------------------------------------

/**
 * ExpressionEngine Discussion Pages Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Pages_upd {

	var $version		= '2.2';

	function Pages_upd($switch=TRUE)
	{
		$this->EE =& get_instance();
	}

	// ----------------------------------------------------------------------
	
	function tabs()
	{
		$tabs['pages'] = array(
			'pages_template_id'	=> array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'htmlbuttons'	=> TRUE,
								'width'			=> '100%'
								),
			
			'pages_uri'		=> array(
								'visible'		=> TRUE,
								'collapse'		=> FALSE,
								'htmlbuttons'	=> TRUE,
								'width'			=> '100%'
								)
				);	
				
		return $tabs;	
	}


	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend, has_publish_fields) VALUES ('Pages', '$this->version', 'y', 'y')";

		if ( ! ee()->db->field_exists('site_pages', 'exp_sites'))
		{
			$sql[] = "ALTER TABLE `exp_sites` ADD `site_pages` TEXT NOT NULL";
		}

		$sql[] = "CREATE TABLE `exp_pages_configuration` (
				`configuration_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`site_id` INT( 8 ) UNSIGNED NOT NULL DEFAULT '1',
				`configuration_name` VARCHAR( 60 ) NOT NULL ,
				`configuration_value` VARCHAR( 100 ) NOT NULL
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}
		
		ee()->load->library('layout');
		ee()->layout->add_layout_tabs($this->tabs(), 'pages');

		return TRUE;
	}


	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Pages'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Pages'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Pages'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Pages_mcp'";
		$sql[] = "ALTER TABLE `exp_sites` DROP `site_pages`";
		$sql[] = "DROP TABLE `exp_pages_configuration`";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}
		
		ee()->load->library('layout');
		ee()->layout->delete_layout_tabs($this->tabs());

		return TRUE;
	}



	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current = '')
	{	

		if ($current === $this->version)
		{
			return FALSE;
		}
		
		if (version_compare($current, '2.1', '<'))
		{
			ee()->db->where('module_name', 'Pages');
			ee()->db->update('modules', array('has_publish_fields' => 'y'));
		}
		
		if (version_compare($current, '2.2', '<'))
		{
			$this->_do_22_update();
		}
	}

	// ----------------------------------------------------------------------

	/**
	 * This is basically identical to the forum update script.
	 *
	 * @return void
	 */
	private function _do_22_update()
	{
		ee()->load->library('layout');
		
		$layouts = ee()->db->get('layout_publish');
		
		if ($layouts->num_rows() === 0)
		{
			return;
		}
		
		$layouts = $layouts->result_array();
		
		$old_pages_fields = array(
							'pages_uri',
							'pages_template_id',
						);	

		foreach ($layouts as &$layout)
		{
			$old_layout = unserialize($layout['field_layout']);

			foreach ($old_layout as $tab => &$fields)
			{
				$field_keys = array_keys($fields);				

				foreach ($field_keys as &$key)
				{
					if (in_array($key, $old_pages_fields))
					{
						$key = 'pages__'.$key;
					}
				}

				$fields = array_combine($field_keys, $fields);
			}

			$layout['field_layout'] = serialize($old_layout);

		}
		
		ee()->db->update_batch('layout_publish', $layouts, 'layout_id');
		
		return TRUE;
	}


}
// END CLASS

/* End of file upd.pages.php */
/* Location: ./system/expressionengine/modules/pages/upd.pages.php */