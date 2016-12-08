<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Table;


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
 * ExpressionEngine CP Members Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Profile extends CP_Controller {

	private $base_url = 'members/profile/settings';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('settings');
		ee()->lang->loadfile('myaccount');

		// check permissions everywhere except for this landing page controller,
		// which redirects in its index function
		if (ee()->uri->segments != array(1 => 'cp', 2 => 'members', 3 => 'profile'))
		{
			$this->permissionCheck();
		}

		$id = ee()->input->get('id');

		if (empty($id))
		{
			$id = ee()->session->userdata['member_id'];
		}

		$qs = array('id' => $id);
		$this->query_string = $qs;
		$this->base_url = ee('CP/URL')->make('members/profile/settings');
		$this->base_url->setQueryStringVariable('id', $id);
		$this->member = ee('Model')->get('Member', $id)->first();

		if (is_null($this->member))
		{
			show_404();
		}

		ee()->lang->loadfile('members');
		ee()->lang->loadfile('myaccount');
		ee()->load->model('member_model');
		ee()->load->library('form_validation');

		$this->generateSidebar();

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('members'), lang('members'));

		ee()->view->header = array(
			'title' => sprintf(lang('profile_header'), $this->member->username)
		);
	}

	protected function permissionCheck()
	{
		if ( ! $this->cp->allowed_group('can_access_members', 'can_edit_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	protected function generateSidebar($active = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		$header = $sidebar->addHeader(lang('personal_settings'), ee('CP/URL')->make('members/profile', $this->query_string));

		if (ee()->uri->uri_string == 'cp/members/profile/settings')
		{
			$header->isActive();
		}

		$list = $header->addBasicList();

		$list->addItem(lang('email_settings'), ee('CP/URL')->make('members/profile/email', $this->query_string));
		$list->addItem(lang('auth_settings'), ee('CP/URL')->make('members/profile/auth', $this->query_string));

		if (ee()->config->item('allow_member_localization') == 'y' OR ee()->session->userdata('group_id') == 1)
		{
			$list->addItem(lang('date_settings'), ee('CP/URL')->make('members/profile/date', $this->query_string));
		}

		$publishing_link = NULL;

		if ($this->cp->allowed_group('can_access_members', 'can_edit_members'))
		{
			$publishing_link = ee('CP/URL')->make('members/profile/publishing', $this->query_string);
		}

		$list = $sidebar->addHeader(lang('publishing_settings'), $publishing_link)
			->addBasicList();

		if ($this->cp->allowed_group('can_edit_html_buttons'))
		{
			$url = ee('CP/URL')->make('members/profile/buttons', $this->query_string);
			$item = $list->addItem(lang('html_buttons'), $url);
			if ($url->matchesTheRequestedURI())
			{
				$item->isActive();
			}
		}


		$url = ee('CP/URL')->make('members/profile/quicklinks', $this->query_string);
		$item = $list->addItem(lang('quick_links'), $url);
		if ($url->matchesTheRequestedURI())
		{
			$item->isActive();
		}

		$url = ee('CP/URL')->make('members/profile/bookmarks', $this->query_string);
		$item = $list->addItem(lang('bookmarklets'), $url);
		if ($url->matchesTheRequestedURI())
		{
			$item->isActive();
		}

		$list->addItem(lang('subscriptions'), ee('CP/URL')->make('members/profile/subscriptions', $this->query_string));

		if (ee()->cp->allowed_group('can_edit_members'))
		{
			$list = $sidebar->addHeader(lang('administration'))
				->addBasicList();

			$list->addItem(lang('blocked_members'), ee('CP/URL')->make('members/profile/ignore', $this->query_string));

			$sa_editing_self = ($this->member->group_id == 1 && $this->member->member_id == ee()->session->userdata['member_id']);
			$group_locked = (ee()->session->userdata['group_id'] != 1 && $this->member->MemberGroup->is_locked);

			if ( ! $sa_editing_self && ! $group_locked)
			{
				$list->addItem(lang('member_group'), ee('CP/URL')->make('members/profile/group', $this->query_string));
			}

			$list->addItem(lang('cp_settings'), ee('CP/URL')->make('members/profile/cp-settings', $this->query_string));

			if ($this->member->member_id != ee()->session->userdata['member_id'])
			{
				$list->addItem(sprintf(lang('email_username'), $this->member->username), ee('CP/URL')->make('utilities/communicate/member/' . $this->member->member_id));

				if (ee()->session->userdata('group_id') == 1)
				{
					$list->addItem(sprintf(lang('login_as'), $this->member->username), ee('CP/URL')->make('members/profile/login', $this->query_string));
				}

				if (ee()->cp->allowed_group('can_delete_members'))
				{
					$list->addItem(sprintf(lang('delete_username'), $this->member->username), ee('CP/URL')->make('members/delete', $this->query_string))
						->asDeleteAction('modal-confirm-remove-member');

					$modal_vars = array(
						'name'		=> 'modal-confirm-remove-member',
						'form_url'	=> ee('CP/URL')->make('members/delete'),
						'checklist' => array(
							array(
								'kind' => lang('members'),
								'desc' => $this->member->username,
							)
						),
						'hidden' => array(
							'bulk_action' => 'remove',
							'selection'   => $this->member->member_id
						)
					);

					ee('CP/Modal')->addModal('member', ee('View')->make('_shared/modal_confirm_remove')->render($modal_vars));
				}
			}
		}
	}

	public function index()
	{
		ee()->functions->redirect($this->base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Generic method for saving member settings given an expected array
	 * of fields.
	 *
	 * @param	array	$sections	Array of sections passed to form view
	 * @return	bool	Success or failure of saving the settings
	 */
	protected function saveSettings($sections)
	{
		// Make sure we're getting only the fields we asked for
		foreach ($sections as $settings)
		{
			foreach ($settings as $setting)
			{
				if ( ! empty($setting['fields']))
				{
					foreach ($setting['fields'] as $field_name => $field)
					{
						$post = ee()->input->post($field_name);

						// birthday fields must be NULL if blank
						if (in_array($field_name, array('bday_d', 'bday_m', 'bday_y')))
						{
							$post = ($post == '') ? NULL : $post;
						}

						// Handle arrays of checkboxes as a special case;
						if ($field['type'] == 'checkbox')
						{
							foreach ($field['choices']  as $property => $label)
							{
								$this->member->$property = in_array($property, $post) ? 'y' : 'n';
							}
						}
						else
						{
							if ($post !== FALSE)
							{
								$this->member->$field_name = $post;
							}
						}

						$name = str_replace('m_field_id_', 'field_ft_', $field_name);

						// Set custom field format override if available, too
						if (strpos($name, 'field_ft_') !== FALSE && ee()->input->post($name))
						{
							$this->member->{"m_$name"} = ee()->input->post($name);
						}
					}
				}
			}
		}

		$validated = $this->member->validate();

		if ($response = $this->ajaxValidation($validated))
		{
			return $response;
		}

		if ($validated->isNotValid())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('member_not_updated'))
				->addToBody(lang('member_not_updated_desc'))
				->now();

			ee()->lang->load('content');
			ee()->view->errors = $validated;

			return FALSE;
		}

		$this->member->save();

		return TRUE;
	}
}
// END CLASS

// EOF
