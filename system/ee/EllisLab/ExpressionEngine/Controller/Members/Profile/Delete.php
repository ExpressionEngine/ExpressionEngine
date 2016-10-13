<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Delete extends Profile {

	private $base_url = 'members/profile/delete';

	/**
	 * Member deletion page
	 */
	public function index()
	{
		if( ! empty($_POST['member']))
		{
			$this->deleteMember();
		}

		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

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

		ee('CP/Alert')->makeInline('shared-form')
			->asWarning()
			->cannotClose()
			->withTitle(lang('delete_member_warning'))
			->addToBody(lang('delete_member_caution'), 'caution')
			->now();

		ee()->view->base_url = $this->base_url;
		ee()->view->cp_page_title = lang('member_delete');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}

	private function deleteMember()
	{
		$this->member->delete();
		ee()->functions->redirect(ee('CP/URL')->make('members'));
	}
}
// END CLASS

// EOF
