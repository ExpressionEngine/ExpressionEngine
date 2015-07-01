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
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
			'personal_settings' => cp_url('members/profile', $qs),
			array(
				'email_settings' => cp_url('members/profile/email', $qs),
				'auth_settings' => cp_url('members/profile/auth', $qs),
				'date_settings' => cp_url('members/profile/date', $qs)
			),
			'publishing_settings' => cp_url('members/profile/publishing', $qs),
			array(
				'quick_links' => cp_url('members/profile/quicklinks', $qs),
				'bookmarklets' => cp_url('members/profile/bookmarks', $qs),
				'subscriptions' => cp_url('members/profile/subscriptions', $qs)
			),
			'administration',
			array(
				'blocked_members' => cp_url('members/profile/ignore', $qs),
				'member_group' => cp_url('members/profile/group', $qs),
				sprintf(lang('email_username'), $this->member->username) => cp_url('utilities/communicate'),
				sprintf(lang('login_as'), $this->member->username) => cp_url('members/profile/login', $qs),
				sprintf(lang('delete_username'), $this->member->username) => array(
					'href' => cp_url('members/delete', $qs),
					'class' => 'remove',
					'attrs' => array(
						'rel' => "modal-confirm-remove",
						'data-confirm-trigger' => "nodeName",
						'data-conditional-modal' => "confirm-trigger",
						'data-confirm-ajax' => cp_url('/members/confirm'),
						'data-confirm-input' => 'selection',
						'data-confirm-text' =>  lang('member') . ': <b>' . htmlentities($this->member->screen_name, ENT_QUOTES) . '</b>'
					)
				)
			)
		));

		$modal_vars = array(
			'name'		=> 'modal-confirm-remove',
			'form_url'	=> cp_url('members/delete'),
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

		ee()->cp->set_breadcrumb(cp_url('members'), lang('members'));
		ee()->cp->set_breadcrumb(cp_url('members/profile', $this->query_string), $this->member->screen_name);
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
