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
 * ExpressionEngine CP Member Profile Publishing Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Publishing extends Profile {

	private $base_url = 'members/profile/publishing';

	/**
	 * Publishing Settings
	 */
	public function index()
	{
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);


		$vars['sections'] = array(
			array(
				array(
					'title' => 'include_in_author_list',
					'desc' => 'include_in_author_list_desc',
					'fields' => array(
						'in_authorlist' => array(
							'type' => 'yes_no',
							'value' => $this->member->in_authorlist
						)
					)
				)
			),
			'rte_settings' => array(
				array(
					'title' => 'rte_enabled',
					'desc' => 'rte_enabled_desc',
					'fields' => array(
						'rte_enabled' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $this->member->rte_enabled
						)
					)
				),
				array(
					'title' => 'rte_toolset',
					'desc' => 'rte_toolset_desc',
					'fields' => array(
						'rte_toolset_id' => array(
							'type' => 'select',
							'choices' => array(
								0 => lang('default')
							),
							'value' => $this->member->rte_toolset_id
						),
					)
				)
			)
		);

		if( ! empty($_POST))
		{
			if ($this->saveSettings($vars['sections']))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();
				ee()->functions->redirect($this->base_url);
			}
		}

		ee()->view->base_url = $this->base_url;
		ee()->view->cp_page_title = lang('publishing_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
