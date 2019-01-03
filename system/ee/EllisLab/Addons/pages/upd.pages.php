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
 * Pages Module update class
 */
class Pages_upd {

	var $version		= '2.2.0';

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
				) DEFAULT CHARACTER SET ".ee()->db->escape_str(ee()->db->char_set)." COLLATE ".ee()->db->escape_str(ee()->db->dbcollat);

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		ee()->load->library('layout');
		ee()->layout->add_layout_tabs($this->tabs(), 'pages');

		return TRUE;
	}


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
		$sql[] = "DROP TABLE `exp_pages_configuration`";

		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		ee()->load->library('layout');
		ee()->layout->delete_layout_tabs($this->tabs());

		return TRUE;
	}



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

// EOF
