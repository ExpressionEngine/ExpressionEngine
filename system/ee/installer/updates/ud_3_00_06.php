<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.6
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
				'_comment_formatting'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Increase the column for storing comment formatting
	 */
	private function _comment_formatting()
	{
		ee()->smartforge->modify_column(
			'channels',
			array(
				'comment_text_formatting' => array(
					'type'			=> 'char',
					'constraint'	=> 40,
					'null'			=> FALSE,
					'default'		=> 'xhtml',
				),
			)
		);

	}
}
/* END CLASS */

/* End of file ud_3_00_06.php */
/* Location: ./system/expressionengine/installer/updates/ud_3_00_06.php */
