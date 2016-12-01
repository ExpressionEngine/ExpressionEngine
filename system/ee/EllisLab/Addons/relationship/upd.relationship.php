<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4.5
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Relationship_upd {

	private $name = 'Relationship';
	public $version = '1.0.0';

	/**
	 * Module Installer
	 *
	 * @return	bool
	 */
	public function install()
	{
		ee()->db->insert(
			'modules',
			array(
				'module_name'			=> $this->name,
				'module_version'		=> $this->version,
				'has_cp_backend'		=> 'n',
				'has_publish_fields'	=> 'n'
			)
		);

		ee()->db->insert_batch(
			'actions',
			array(
				array(
					'class'		=> $this->name,
					'method'	=> 'entryList'
				)
			)
		);

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @return	bool
	 */
	public function uninstall()
	{
		$module_id = ee()->db->select('module_id')
			->get_where('modules', array( 'module_name' => $this->name ))
			->row('module_id');

		ee()->db->delete(
			'module_member_groups',
			array('module_id' => $module_id)
		);

		ee()->db->delete(
			'modules',
			array('module_name' => $this->name)
		);

		ee()->db->where('class', $this->name)
			->or_where('class', $this->name . '_mcp')
			->delete('actions');

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @return	bool
	 */
	public function update($current = '')
	{
		return TRUE;
	}

}
// END CLASS

// EOF
