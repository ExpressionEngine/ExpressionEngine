<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.0
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
				'_update_email_cache_table',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// -------------------------------------------------------------------------

	/**
	 * Removes 3 columns and adds 1 column to the email_cache table
	 *
	 * @access private
	 * @return void
	 */
	private function _update_email_cache_table()
	{
		ee()->smartforge->drop_column('email_cache', 'mailinglist');
		ee()->smartforge->drop_column('email_cache', 'priority');

		ee()->smartforge->add_column(
			'email_cache',
			array(
				'attachments' => array(
					'type'			=> 'mediumtext',
					'null'			=> TRUE
				)
			)
		);
	}
}
/* END CLASS */

/* End of file ud_300.php */
/* Location: ./system/expressionengine/installer/updates/ud_300.php */
