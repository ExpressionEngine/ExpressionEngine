<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Member Profile Personal Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Settings extends Profile {

	private $base_url = 'members/profile/settings';

	/**
	 * Personal Settings
	 */
	public function index()
	{
		$vars['sections'] = array(
		);

		ee()->view->base_url = cp_url($this->base_url);
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('personal_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_save_settings_working';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Settings.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Settings.php */
