<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.9.0
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
				'_update_template_routes_table',
				'_set_hidden_template_indicator',
				'_ensure_channel_combo_loader_action_integrity',
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
					'null'			=> TRUE
				)
			)
		);
	}

	/**
	 * If this was a pre-2.7 install and never had Safecracker installed,
	 * there could be a missing action for the Channel class. So let's
	 * make sure it exists and add it if it doesn't.
	 *
	 * @access private
	 * @return void
	 **/
	private function _ensure_channel_combo_loader_action_integrity()
	{
		$row_data = array(
			'class' => 'Channel',
			'method' => 'combo_loader'
		);

		ee()->db->where($row_data);
		$count = ee()->db->count_all_results('actions');

		if ($count == 0)
		{
			ee()->db->insert('actions', $row_data);
		}
	}

	// -------------------------------------------------------------------

}
/* END CLASS */

/* End of file ud_290.php */
/* Location: ./system/expressionengine/installer/updates/ud_290.php */
