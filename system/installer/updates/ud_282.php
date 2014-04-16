<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.8.2
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
		$steps = new ProgressIterator(
			array(
				'_set_hidden_template_indicator'
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
	 * Set the hidden_template_indicator config item to a period if the site has
	 * no specific hidden template indicator.
	 */
	private function _set_hidden_template_indicator()
	{
		if (ee()->config->item('hidden_template_indicator') === FALSE)
		{
			ee()->config->_update_config(array(
				'hidden_template_indicator' => '.'
			));
		}
	}
}
/* END CLASS */

/* End of file ud_282.php */
/* Location: ./system/expressionengine/installer/updates/ud_282.php */