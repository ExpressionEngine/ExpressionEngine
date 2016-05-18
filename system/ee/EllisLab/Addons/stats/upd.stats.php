<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Stats Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Stats_upd {

	var $version	= '2.0.0';

	function __construct()
	{
		ee()->load->dbforge();
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
		$data = array(
					'module_name' => 'Stats',
					'module_version' => $this->version,
					'has_cp_backend' => 'n'
					);

		ee()->db->insert('modules', $data);

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
		ee()->db->select('module_id');
		ee()->db->from('modules');
		ee()->db->where('module_name', 'Stats');
		$query = ee()->db->get();

		ee()->db->delete('module_member_groups', array('module_id' => $query->row('module_id')));
		ee()->db->delete('modules', array('module_name' => 'Stats'));
		ee()->db->delete('actions', array('class' => 'Stats'));
		ee()->db->delete('actions', array('class' => 'Stats_mcp'));

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current='')
	{
		if (version_compare($current, $this->version, '=='))
		{
			return FALSE;
		}

		if (version_compare($current, '2.0', '<'))
		{
			ee()->dbforge->drop_column('stats', 'weblog_id');
		}

		return TRUE;
	}

}
// END CLASS

// EOF
