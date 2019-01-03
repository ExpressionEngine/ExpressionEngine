<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

use CP_Controller;

/**
 * Member Profile Publishing Settings Controller
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
			)
		);

		if (ee('Addon')->get('rte')->isInstalled())
		{
			$vars['sections']['rte_settings'] = array(
				array(
					'title' => 'rte_enabled',
					'desc' => 'rte_enabled_desc',
					'fields' => array(
						'rte_enabled' => array(
							'type' => 'yes_no',
							'value' => $this->member->rte_enabled
						)
					)
				),
				array(
					'title' => 'rte_toolset',
					'desc' => 'rte_toolset_desc',
					'fields' => array(
						'rte_toolset_id' => array(
							'type' => 'radio',
							'choices' => ee('Model')->get('rte:Toolset')->all()->getDictionary('toolset_id', 'name'),
							'value' => $this->member->rte_toolset_id
						),
					)
				)
			);
		}

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
