<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

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

		ee()->lang->loadfile('members');
		ee()->lang->loadfile('myaccount');
		ee()->load->model('member_model');
		ee()->load->library('form_validation');

		// Register our menu
		ee()->menu->register_left_nav(array(
			'personal_settings' => ee('CP/URL', 'members/profile', $qs),
			array(
				'email_settings' => ee('CP/URL', 'members/profile/email', $qs),
				'auth_settings' => ee('CP/URL', 'members/profile/auth', $qs),
				'date_settings' => ee('CP/URL', 'members/profile/date', $qs)
			),
			'publishing_settings' => ee('CP/URL', 'members/profile/publishing', $qs),
			array(
				'quick_links' => ee('CP/URL', 'members/profile/quicklinks', $qs),
				'bookmarklets' => ee('CP/URL', 'members/profile/bookmarks', $qs),
				'subscriptions' => ee('CP/URL', 'members/profile/subscriptions', $qs)
			),
			'administration',
			array(
				'blocked_members' => ee('CP/URL', 'members/profile/ignore', $qs),
				'member_group' => ee('CP/URL', 'members/profile/group', $qs),
				sprintf(lang('email_username'), $this->member->username) => ee('CP/URL', 'utilities/communicate/member/' . $this->member->member_id),
				sprintf(lang('login_as'), $this->member->username) => ee('CP/URL', 'members/profile/login', $qs),
				sprintf(lang('delete_username'), $this->member->username) => array(
					'href' => ee('CP/URL', 'members/delete', $qs),
					'class' => 'remove',
					'attrs' => array(
						'rel' => "modal-confirm-remove",
						'data-confirm-trigger' => "nodeName",
						'data-conditional-modal' => "confirm-trigger",
						'data-confirm-ajax' => ee('CP/URL', '/members/confirm'),
						'data-confirm-input' => 'selection',
						'data-confirm-text' =>  lang('member') . ': <b>' . htmlentities($this->member->screen_name, ENT_QUOTES) . '</b>'
					)
				)
			)
		));

		$modal_vars = array(
			'name'		=> 'modal-confirm-remove',
			'form_url'	=> ee('CP/URL', 'members/delete'),
			'hidden'	=> array(
				'bulk_action'	=> 'remove'
			)
		);

		$modal = ee()->load->view('_shared/modal_confirm_remove', $modal_vars, TRUE);
		$modal .= "<input type='hidden' name='selection' value='{$this->member->member_id}' />";
		ee()->view->blocks['modals'] = $modal;

		ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->set_breadcrumb(ee('CP/URL', 'members'), lang('members'));
		ee()->cp->set_breadcrumb(ee('CP/URL', 'members/profile', $this->query_string), $this->member->screen_name);
	}

	// --------------------------------------------------------------------

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
			ee()->view->set_message('issue', lang('cp_message_issue'), ul($validated->getAllErrors()), TRUE);

			return FALSE;
		}

		$this->member->save();

		return TRUE;
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Members.php */
