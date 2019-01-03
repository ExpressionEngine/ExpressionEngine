<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Channels;

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use Mexitek\PHPColors\Color;

/**
 * Channel Status Controller
 */
class Status extends AbstractChannelsController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_create_statuses',
			'can_edit_statuses',
			'can_delete_statuses'
		))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	/**
	 * AJAX endpoint for reordering statuses
	 */
	public function reorder()
	{
		if ( ! ee()->cp->allowed_group('can_edit_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$statuses = ee('Model')->get('Status')->all()->indexBy('status_id');

		foreach (ee('Request')->post('order') as $order => $status)
		{
			$statuses[$status['id']]->status_order = $order + 1;
			$statuses[$status['id']]->save();
		}

		return ['success'];
	}

	/**
	 * Remove status handler
	 */
	public function remove()
	{
		if ( ! ee()->cp->allowed_group('can_delete_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$status_id = ee('Request')->post('content_id');

		if ( ! empty($status_id))
		{
			ee('Model')->get('Status', $status_id)->delete();
		}

		return ['success'];
	}

	/**
	 * New status form
	 */
	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->statusForm();
	}

	/**
	 * Edit status form
	 */
	public function edit($status_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_statuses'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		return $this->statusForm($status_id);
	}

	/**
	 * Status creation/edit form
	 *
	 * @param	int	$status_id	ID of status to edit
	 */
	private function statusForm($status_id = NULL)
	{
		$vars = [];
		if (is_null($status_id))
		{
			$alert_key = 'created';
			$vars['cp_page_title'] = lang('create_status');
			$vars['base_url'] = ee('CP/URL')->make('channels/status/create');
			$status = ee('Model')->make('Status');
		}
		else
		{
			$status = ee('Model')->get('Status', $status_id)->first();

			if ( ! $status)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			$vars['cp_page_title'] = lang('edit_status');
			$vars['base_url'] = ee('CP/URL')->make('channels/status/edit/'.$status_id);
		}

		// Member IDs NOT in $no_access have access...
		list($allowed_groups, $member_groups) = $this->getAllowedGroups(is_null($status_id) ? NULL : $status);

		// Create the status example
		$status_style = '';
		if ( ! in_array($status->status, array('open', 'closed')) && $status->highlight != '')
		{
			$foreground = $this->calculateForegroundFor($status->highlight);
			$status_style = "style='background-color: #{$status->highlight}; border-color: #{$status->highlight}; color: #{$foreground};'";
		}

		$status_name = (empty($status->status)) ? lang('status') : $status->status;

		$status_class = str_replace(' ', '_', strtolower($status->status));
		$status_example = '<span class="status-tag st-'.$status_class.'" '.$status_style.'>'.$status_name.'</span>';

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'status_name_desc',
					'fields' => array(
						'status' => array(
							'type' => 'text',
							'value' => $status->getRawProperty('status'),
							'required' => TRUE,
							'disabled' => in_array($status->getRawProperty('status'), ['open', 'closed'])
								? 'disabled' : NULL
						)
					)
				),
				array(
					'title' => 'highlight_color',
					'desc' => 'highlight_color_desc',
					'example' => $status_example,
					'fields' => array(
						'highlight' => array(
							'type' => 'text',
							'attrs' => 'class="color-picker"',
							'value' => $status->highlight ?: '000000',
							'required' => TRUE
						)
					)
				)
			),
			'permissions' => array(
				ee('CP/Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('category_permissions_warning'))
					->addToBody(
						sprintf(lang('category_permissions_warning2'), '<span class="icon--caution" title="exercise caution"></span>'),
						'caution'
					)
					->cannotClose()
					->render(),
				array(
					'title' => 'status_access',
					'desc' => 'status_access_desc',
					'caution' => TRUE,
					'fields' => array(
						'status_access' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $allowed_groups,
							'no_results' => [
								'text' => sprintf(lang('no_found'), lang('member_groups'))
							]
						)
					)
				)
			)
		);

		if ( ! empty($_POST))
		{
			$status = $this->setWithPost($status);
			$result = $status->validate();

			if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				$status->save();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('status_'.$alert_key))
					->addToBody(sprintf(lang('status_'.$alert_key.'_desc'), $status->status))
					->defer();

				return ['saveId' => $status->getId()];
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('status_not_'.$alert_key))
					->addToBody(lang('status_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		$vars['ajax_validate'] = TRUE;
		$vars['buttons'] = [
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save',
				'text' => 'save',
				'working' => 'btn_saving'
			],
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_new',
				'text' => 'save_and_new',
				'working' => 'btn_saving'
					]
		];

		return ee('View')->make('ee:_shared/form')->render($vars);
	}

	private function setWithPost($status)
	{
		if ( ! in_array($status->getRawProperty('status'), ['open', 'closed']))
		{
			$status->status = ee()->input->post('status');
		}

		$status->highlight = ltrim(ee()->input->post('highlight'), '#');

		$access = ee()->input->post('status_access') ?: array();

		$no_access = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array_merge(array(1,2,3,4), $access))
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		if ($no_access->count() > 0)
		{
			$status->NoAccess = $no_access;
		}
		else
		{
			// Remove all member groups from this status
			$status->NoAccess = NULL;
		}

		return $status;
	}

	/**
	 * Retrieve the foreground color for a given status color
	 *
	 * @param string $color The hex color for the background
	 * @return void
	 */
	public function getForegroundColor($color = '')
	{
		$color = ee()->input->post('highlight');
		$foreground = $this->calculateForegroundFor($color);
		ee()->output->send_ajax_response($foreground);
	}

	/**
	 * Retrieve the foreground color for a given status color
	 *
	 * @param string $color The hex color for the background
	 * @return string The hex color best suited for the background color
	 */
	protected function calculateForegroundFor($background)
	{
		try
		{
			$background = new Color($background);
			$foreground = ($background->isLight())
				? $background->darken(100)
				: $background->lighten(100);
		}
		catch (\Exception $e)
		{
			$foreground = 'ffffff';
		}

		return $foreground;
	}

	/**
	 * Returns an array of member group IDs allowed to use this status
	 * in the form of id => title, along with an array of all member
	 * groups in the same format
	 *
	 * @param	model	$status		Model object for status
	 * @return	array	Array containing each of the arrays mentioned above
	 */
	private function getAllowedGroups($status = NULL)
	{
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('group_id', 'NOT IN', array(1,2,3,4))
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_title')
			->all()
			->getDictionary('group_id', 'group_title');

		if ( ! empty($_POST))
		{
			if (isset($_POST['status_access']))
			{
				return array($_POST['status_access'], $member_groups);
			}

			return array(array(), $member_groups);
		}

		$no_access = array();
		if ($status !== NULL)
		{
			$no_access = $status->getNoAccess()->pluck('group_id');
		}

		$allowed_groups = array_diff(array_keys($member_groups), $no_access);

		// Member IDs NOT in $no_access have access...
		return array($allowed_groups, $member_groups);
	}
}

// EOF
