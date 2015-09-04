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
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Profile extends CP_Controller {

	private $base_url = 'members/profile/settings';

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('myaccount');

		if ( ! $this->cp->allowed_group('can_access_members'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $this->cp->allowed_group('can_edit_members'))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = ee()->input->get('id');

		if (empty($id))
		{
			$id = ee()->session->userdata['member_id'];
		}

		$qs = array('id' => $id);
		$this->query_string = $qs;
		$this->base_url = ee('CP/URL', 'members/profile/settings');
		$this->base_url->setQueryStringVariable('id', $id);
		$this->member = ee()->api->get('Member')->filter('member_id', $id)->first();

		if (is_null($this->member))
		{
			show_404();
		}

		ee()->lang->loadfile('members');
		ee()->lang->loadfile('myaccount');
		ee()->load->model('member_model');
		ee()->load->library('form_validation');

		$this->generateSidebar();

		ee()->cp->set_breadcrumb(ee('CP/URL', 'members'), lang('members'));

		ee()->view->header = array(
			'title' => sprintf(lang('profile_header'), $this->member->username)
		);
	}

	protected function generateSidebar($active = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		$header = $sidebar->addHeader(lang('personal_settings'), ee('CP/URL', 'members/profile', $this->query_string));

		if (ee()->uri->uri_string == 'cp/members/profile/settings')
		{
			$header->isActive();
		}

		$list = $header->addBasicList();

		$list->addItem(lang('email_settings'), ee('CP/URL', 'members/profile/email', $this->query_string));
		$list->addItem(lang('auth_settings'), ee('CP/URL', 'members/profile/auth', $this->query_string));
		$list->addItem(lang('date_settings'), ee('CP/URL', 'members/profile/date', $this->query_string));

		$list = $sidebar->addHeader(lang('publishing_settings'), ee('CP/URL', 'members/profile/publishing', $this->query_string))
			->addBasicList();

		$url = ee('CP/URL', 'members/profile/buttons', $this->query_string);
		$item = $list->addItem(lang('html_buttons'), $url);
		if ($url->matchesTheRequestedURI())
		{
			$item->isActive();
		}

		$url = ee('CP/URL', 'members/profile/quicklinks', $this->query_string);
		$item = $list->addItem(lang('quick_links'), $url);
		if ($url->matchesTheRequestedURI())
		{
			$item->isActive();
		}

		$url = ee('CP/URL', 'members/profile/bookmarks', $this->query_string);
		$item = $list->addItem(lang('bookmarklets'), $url);
		if ($url->matchesTheRequestedURI())
		{
			$item->isActive();
		}

		$list->addItem(lang('subscriptions'), ee('CP/URL', 'members/profile/subscriptions', $this->query_string));

		$list = $sidebar->addHeader(lang('administration'))
			->addBasicList();

		$list->addItem(lang('blocked_members'), ee('CP/URL', 'members/profile/ignore', $this->query_string));
		$list->addItem(lang('member_group'), ee('CP/URL', 'members/profile/group', $this->query_string));
		$list->addItem(lang('cp_settings'), ee('CP/URL', 'members/profile/cp-settings', $this->query_string));

		if ($this->member->member_id != ee()->session->userdata['member_id'])
		{
			$list->addItem(sprintf(lang('email_username'), $this->member->username), ee('CP/URL', 'utilities/communicate/member/' . $this->member->member_id));
			$list->addItem(sprintf(lang('login_as'), $this->member->username), ee('CP/URL', 'members/profile/login', $this->query_string));
			$list->addItem(sprintf(lang('delete_username'), $this->member->username), ee('CP/URL', 'members/delete', $this->query_string))
				->asDeleteAction('modal-confirm-remove-member');
		}

		$modal_vars = array(
			'name'		=> 'modal-confirm-remove-member',
			'form_url'	=> ee('CP/URL', 'members/delete'),
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
						if ($field['type'] == 'checkbox' && is_array($post))
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
					}
				}
			}
		}

		$validated = $this->member->validate();

		if ($validated->isNotValid())
		{
			ee()->load->helper('html_helper');
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('cp_message_issue'))
				->addToBody($validated->getAllErrors())
				->now();

			return FALSE;
		}

		$this->member->save();

		return TRUE;
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Members.php */
