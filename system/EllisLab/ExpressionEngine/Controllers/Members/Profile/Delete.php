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
 * ExpressionEngine CP Member Delete Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Delete extends Profile {

	private $base_url = 'members/profile/delete';

	/**
	 * Member deletion page
	 */
	public function index()
	{
		if( ! empty($_POST))
		{
			$this->deleteMember();
		}

		$this->base_url = cp_url($this->base_url, $this->query_string);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'Members:',
					'desc' => $this->member->username,
					'fields' => array(
						'member' => array(
							'type' => 'hidden',
							'value' => $this->member->member_id
						)
					)
				)
			)
		);

		$message = array(
			'warning' => lang('warning'),
			'warning_desc' => lang('delete_member_warning'),
			'caution' => lang('delete_member_caution'),
		);

		$html = ee()->load->view('account/delete_warning', $message, TRUE);
		$alert = array('type' => 'warn', 'custom' => $html);

		ee()->view->set_alert('inline', $alert, FALSE);
		ee()->view->base_url = $this->base_url;
		ee()->view->cp_page_title = lang('member_delete');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_save_settings';
		ee()->cp->render('settings/form', $vars);
	}

	private function deleteMember()
	{
		$this->member->delete();
		ee()->functions->redirect(cp_url('members'));
	}
}
// END CLASS

/* End of file Delete.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Profile/Delete.php */
