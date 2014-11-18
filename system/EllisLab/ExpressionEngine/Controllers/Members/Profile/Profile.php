<?php

namespace EllisLab\ExpressionEngine\Controllers\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

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

		if ( ! $this->cp->allowed_group('can_access_members'))
		{
			show_error(lang('unauthorized_access'));
		}

		$id = ee()->input->get('id');
		$qs = array('id' => $id);
		$this->query_string = $qs;
		$this->base_url = new URL('members/profile/settings');
		$this->base_url->setQueryStringVariable('id', $id);
		$this->member = ee()->api->get('Member')->filter('member_id', $id)->first();

		ee()->lang->loadfile('members');
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
				'bookmarks' => cp_url('members/profile/bookmarks', $qs),
				'subscriptions' => cp_url('members/profile/subscriptions', $qs)
			),
			'administration',
			array(
				'blocked_members' => cp_url('members/profile/ignore', $qs),
				'member_group' => cp_url('members/profile/group', $qs),
				'email_username' => cp_url('members/profile/communicate', $qs),
				'login_as' => cp_url('members/profile/login', $qs),
				'delete_username' => cp_url('members/profile/delete', $qs)
			)
		));
	}

	// --------------------------------------------------------------------

	public function index()
	{
		ee()->functions->redirect($this->base_url);
	}
}
// END CLASS

/* End of file Members.php */
/* Location: ./system/expressionengine/controllers/cp/Members/Members.php */
