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
 * Member Profile CP Settings Controller
 */
class CpSettings extends Profile {

	private $base_url = 'members/profile/cp-settings';

	/**
	 * Date Settings
	 */
	public function index()
	{
		$field['allowed_channels'] = array();
		$all_sites_have_channels = TRUE;
		$assigned_channels = $this->member->MemberGroup->AssignedChannels->pluck('channel_id');

		// If MSM is enabled, let them choose a channel for each site, should they
		// want to redirect to the publish form on each site
		if (bool_config_item('multiple_sites_enabled'))
		{
			$sites = ee('Model')->get('Site')->all();
			$field['sites'] = $sites->getDictionary('site_id', 'site_label');

			foreach ($sites as $site)
			{
				// Get only the channels they're allowed to post in
				$field['allowed_channels'][$site->getId()] = $site->Channels->filter(function($channel)
				{
					return (ee()->session->userdata('group_id') == 1 OR ( ! empty($assigned_channels) && in_array($channel->getId(), $assigned_channels)));
				})->getDictionary('channel_id', 'channel_title');

				// No channels? Let them know
				if (empty($field['allowed_channels'][$site->getId()]))
				{
					$all_sites_have_channels = FALSE;
					$field['allowed_channels'][$site->getId()][0] = strip_tags(lang('no_channels'));
				}
			}
		}
		else
		{
			$field['allowed_channels'] = NULL;

			if ($assigned_channels)
			{
				$allowed_channels = ee('Model')->get('Channel')
					->filter('site_id', ee()->config->item('site_id'));
				$allowed_channels->filter('channel_id', 'IN', $assigned_channels);
				$field['allowed_channels'] = $allowed_channels->all()->getDictionary('channel_id', 'channel_title');
			}


			if (empty($field['allowed_channels']))
			{
				$all_sites_have_channels = FALSE;
				$field['allowed_channels'][0] = strip_tags(lang('no_channels'));
			}

			$site_id = ee()->config->item('site_id');
			$field['selected_channel'] = isset($this->member->cp_homepage_channel[$site_id]) ? $this->member->cp_homepage_channel[$site_id] : 0;
		}

		$field['member'] = $this->member;
		$field['all_sites_have_channels'] = $all_sites_have_channels;

		$vars['sections'] = array(
			array(
				array(
					'title' => 'default_cp_homepage',
					'desc' => 'default_cp_homepage_myaccount_desc',
					'fields' => array(
						'cp_homepage_custom' => array(
							'type' => 'html',
							'content' => ee('View')->make('account/cp_homepage_setting')->render($field)
						)
					)
				)
			)
		);

		$base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

		if ( ! empty($_POST))
		{
			$validator = ee('Validation')->make();

			$validator->defineRule('whenTypeIs', function($key, $value, $parameters, $rule)
			{
				if ($_POST['cp_homepage'] != $parameters[0])
				{
					$rule->skip();
				}

				return TRUE;
			});

			$validator->defineRule('validateHomepageChannel', function () use ($all_sites_have_channels)
			{
				if ( ! $all_sites_have_channels)
				{
					return 'must_have_channels';
				}

				return TRUE;
			});

			$validator->setRules(array(
				'cp_homepage' => 'whenTypeIs[publish_form]|validateHomepageChannel',
				'cp_homepage_custom' => 'whenTypeIs[custom]|required'
			));

			$result = $validator->validate($_POST);

			if (AJAX_REQUEST)
			{
				$field = ee()->input->post('ee_fv_field');

				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
			}

			if ($result->isValid())
			{
				// Only set what we need to set to prevent POST fiddling
				$this->member->set(array(
					'cp_homepage' => $_POST['cp_homepage'],
					'cp_homepage_channel' => $_POST['cp_homepage_channel'],
					'cp_homepage_custom' => $_POST['cp_homepage_custom']
				))->save();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('member_updated'))
					->addToBody(lang('member_updated_desc'))
					->defer();

				ee()->functions->redirect($base_url);
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('settings_save_error'))
					->addToBody(lang('settings_save_error_desc'))
					->now();
			}
		}

		ee()->javascript->output("
			$(document).ready(function () {

				$('input[type=\"radio\"]').click(function(){
					$('label.child').toggleClass('chosen', $(this).val() == 'publish_form');
				});
			});
		");

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('cp_settings');
		ee()->view->save_btn_text = 'btn_save_settings';
		ee()->view->save_btn_text_working = 'btn_saving';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
