<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.8.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_update_extension_quick_tabs',
				'_update_member_table',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	// -------------------------------------------------------------------------

	private function _update_extension_quick_tabs()
	{
		$members = ee()->db->select('member_id, quick_tabs')
			->where('quick_tabs IS NOT NULL')
			->like('quick_tabs', 'toggle_extension')
			->get('members')
			->result_array();

		if ( ! empty($members))
		{
			foreach ($members as $index => $member)
			{
				$members[$index]['quick_tabs'] = str_replace('toggle_extension_confirm', 'toggle_all', $members[$index]['quick_tabs']);
				$members[$index]['quick_tabs'] = str_replace('toggle_extension', 'toggle_install', $members[$index]['quick_tabs']);
			}

			ee()->db->update_batch('members', $members, 'member_id');
		}
	}

	// -------------------------------------------------------------------------

	private function _update_member_table()
	{
		// Add new columns
		ee()->smartforge->add_column(
			'members',
			array(
				'date_format'    => array(
					'type'       => 'varchar',
					'constraint' => 8,
					'null'       => FALSE,
					'default'    => '%Y-%m-%d'
				),
				'include_seconds' => array(
					'type'        => 'char',
					'constraint'  => 1,
					'null'        => FALSE,
					'default'     => 'n'
				)
			),
			'time_format'
		);

		// Modify the default value of time_format
		ee()->smartforge->modify_column(
			'members',
			array(
				'time_format'    => array(
					'name'       => 'time_format',
					'type'       => 'char',
					'constraint' => 2,
					'null'       => FALSE,
					'default'    => '12'
				)
			)
		);

		// Update all the members
		ee()->db->where('time_format', 'us')->update('members', array('date_format' => '%m/%d/%y', 'time_format' => '12'));
		ee()->db->where('time_format', 'eu')->update('members', array('date_format' => '%d/%m/%y', 'time_format' => '24'));
		$include_seconds = ee()->config->item('include_seconds') ? ee()->config->item('include_seconds') : 'n';
		ee()->db->update('members', array('include_seconds' => $include_seconds));
	}

}
/* END CLASS */

/* End of file ud_280.php */
/* Location: ./system/expressionengine/installer/updates/ud_280.php */
