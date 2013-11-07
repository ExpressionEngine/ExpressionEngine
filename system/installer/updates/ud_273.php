<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7.3
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
				'_update_email_db_columns',
				'_update_session_config_names',
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
	 * Change email columns to varchar(75)
	 * @return void
	 */
	private function _update_email_db_columns()
	{
		$changes = array(
			'members' => 'email',
			'email_cache' => 'from_email',
			'email_console_cache' => 'recipient',
		);

		foreach ($changes as $table => $column)
		{
			ee()->smartforge->modify_column(
				$table,
				array(
					$column => array(
						'name' 			=> $column,
						'type' 			=> 'VARCHAR',
						'constraint' 	=> 75,
						'null' 			=> FALSE
					)
				)
			);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Renames admin_session_type and user_session_type in the config
	 *
	 * @return void
	 **/
	private function _update_session_config_names()
	{
		$new_config_items = array(
			'cp_session_type'      => ee()->config->item('admin_session_type'),
			'website_session_type' => ee()->config->item('user_session_type'),
			'website_session_ttl'  => ee()->config->item('user_session_ttl')
		);
		$remove_config_items = array(
			'admin_session_type' => '',
			'user_session_type'  => '',
			'user_session_ttl'   => ''
		);
		ee()->config->_update_config($new_config_items, $remove_config_items);
	}
}
/* END CLASS */

/* End of file ud_273.php */
/* Location: ./system/expressionengine/installer/updates/ud_273.php */