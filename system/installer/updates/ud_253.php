<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5.3
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
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new ProgressIterator(
			array(
				'_change_site_preferences_column_type',
				'_truncate_tables',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Changes column type for the `site_system_preferences` column in
	 * `sites` from TEXT to MEDIUMTEXT
	 */
	private function _change_site_preferences_column_type()
	{
		ee()->smartforge->modify_column(
			'sites',
			array(
				'site_system_preferences' => array(
					'name' => 'site_system_preferences',
					'type' => 'mediumtext'
				)
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Truncates `security_hashes` and `throttle` tables in response to bug
	 * #17795 where these tables may not be emptied regularly. Now that the
	 * fix is in place, to help prevent a case where EE will hang when
	 * trying to clear 15 million records based on a non-indexed date field,
	 * let's just clear out the tables.
	 */
	private function _truncate_tables()
	{
		ee()->db->truncate('security_hashes');
		ee()->db->truncate('throttle');
	}
}
/* END CLASS */

/* End of file ud_253.php */
/* Location: ./system/expressionengine/installer/updates/ud_253.php */