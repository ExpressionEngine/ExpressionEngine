<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.8.1
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
				'_update_template_routes_table'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// -------------------------------------------------------------------

	/**
	 * Add a column to the Template Routes table for storing the parse order
	 * 
	 * @access private
	 * @return void
	 */
	private function _update_template_routes_table()
	{
		ee()->smartforge->add_column(
			'template_routes',
			array(
				'order' => array(
					'type'			=> 'int',
					'constraint'    => 10,
					'unsigned'		=> TRUE,
					'null'			=> FALSE
				)
			)
		);
	}

}
/* END CLASS */

/* End of file ud_282.php */
/* Location: ./system/expressionengine/installer/updates/ud_282.php */
