<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7.4
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
				'_update_specialty_templates',
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
	 * Was updated in 2.6 and 2.7, but we need to add the line with {username}.
	 * But only to installations that haven't modified the default.
	 */
	private function _update_specialty_templates()
	{
		ee()->db->where('template_name', 'reset_password_notification');
		ee()->db->delete('specialty_templates');

		$old_data = '{name},

To reset your password, please go to the following page:

{reset_url}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}';

		$new_data = array(
			'template_data'=>'{name},

To reset your password, please go to the following page:

{reset_url}

Then log in with your username: {username}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}');

		ee()->db->where('template_name', 'forgot_password_instructions')
			->where('template_data', $old_data)
			->update('specialty_templates', $new_data);

	}
}
/* END CLASS */

/* End of file ud_274.php */
/* Location: ./system/expressionengine/installer/updates/ud_274.php */