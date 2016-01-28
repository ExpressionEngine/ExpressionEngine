<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
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

// EOF
