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
				'_update_extension_quick_tabs'
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

}
/* END CLASS */

/* End of file ud_280.php */
/* Location: ./system/expressionengine/installer/updates/ud_280.php */
