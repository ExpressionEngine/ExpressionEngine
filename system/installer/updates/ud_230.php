<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
 * @since       Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	private $EE;
	var $version_suffix = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new ProgressIterator(
			array(
				'_update_session_table',
				'_fix_emoticon_config',
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
	 * Update Session Table
	 *
	 * Drops site_id field from sessions table
	 *
	 * @return 	void
	 */
	private function _update_session_table()
	{
		// Drop site_id
		ee()->smartforge->drop_column('sessions', 'site_id');
    }

	// --------------------------------------------------------------------

	/**
	 * Replaces emoticon_path in your config file with emoticon_url
	 *
	 * @return 	void
	 */
	private function _fix_emoticon_config()
	{
		if ($emoticon_url = ee()->config->item('emoticon_path'))
		{
			ee()->config->_update_config(array('emoticon_url' => $emoticon_url), array('emoticon_path' => ''));
		}
	}
}
/* END CLASS */

/* End of file ud_230.php */
/* Location: ./system/expressionengine/installer/updates/ud_230.php */