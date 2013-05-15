<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.6.1
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
 * @link		http://expressionengine.com
 */
class Updater {
	
	public $version_suffix = '';

	
	/**
	 * Constructor
	 */
	public function __construct()
	{
	}
	
	// --------------------------------------------------------------------
	
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
				'_update_specialty_templates'
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
	 * Update Specialty Templates
	 *
	 * Was updated in 2.6, but new installs got the old template
	 */
	private function _update_specialty_templates()
	{
		ee()->db->where('template_name', 'reset_password_notification');
		ee()->db->delete('specialty_templates');

		$data = array(
			'template_data'=>'{name},

To reset your password, please go to the following page:

{reset_url}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}');	

		ee()->db->where('template_name', 'forgot_password_instructions')
			->update('specialty_templates', $data);
		
	}
}	
/* END CLASS */

/* End of file ud_261.php */
/* Location: ./system/expressionengine/installer/updates/ud_261.php */