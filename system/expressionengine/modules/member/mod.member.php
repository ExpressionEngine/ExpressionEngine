<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * Member Management Class
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

 /*
	Multi Site Login

	The login routine can set cookies for multiple domains if needed.
	This allows users who run separate domains for each channel to have
	a way to enable users to log-in once and remain logged-in across
	domains.  In order to use this feature this array index must be
	added to the config file:

	$config['multi_login_sites'] = "http://www.siteone.com/|http://www.sitetwo.com";

	Separate each domain with a pipe.
 */

class Member {

	var $trigger			= 'member';
	var $theme_class		= 'profile_theme';
	var $request			= 'public_profile';
	var $no_menu 			= array(
						'public_profile', 'memberlist', 'do_member_search',
						'member_search', 'register', 'smileys', 'login',
						'unpw_update', 'email_console', 'send_email',
						'aim_console', 'icq_console', 'forgot_password', 'reset_password',
						'delete', 'member_mini_search', 'do_member_mini_search',
					);

	var $no_login 			= array(
						'public_profile', 'memberlist', 'do_member_search',
						'member_search', 'register', 'forgot_password', 'unpw_update',
						'reset_password'
					);

	var $id_override		= array(
						'edit_subscriptions', 'memberlist', 'member_search',
						'browse_avatars', 'messages', 'unpw_update'
					);

	var $no_breadcrumb 		= array(
						'email_console', 'send_email', 'aim_console',
						'icq_console', 'member_mini_search', 'do_member_mini_search'
					);

	var $simple_page		= array(
						'email_console', 'send_email', 'aim_console',
						'icq_console', 'smileys', 'member_mini_search', 'do_member_mini_search'
					);

	var $page_title 		= '';
	var $basepath			= '';
	var $forum_path			= '';
	var $image_url			= '';
	var $theme_path			= '';
	var $cur_id				= '';
	var $uri_extra			= '';
	var $return_data		= '';
	var $javascript			= '';
	var $head_extra			= '';
	var $var_single			= '';
	var $var_pair			= '';
	var $var_cond			= '';
	var $css_file_path		= '';
	var $board_id			= '';
	var $show_headings 		= TRUE;
	var $in_forum			= FALSE;
	var $is_admin			= FALSE;
	var $breadcrumb			= TRUE;
	var $crumb_map 			= array(
								'profile'				=>	'your_control_panel',
								'delete'				=>	'mbr_delete',
								'reset_password'		=>  'mbr_reset_password',
								'forgot_password'		=>	'mbr_forgotten_password',
								'login'					=>	'mbr_login',
								'unpw_update'			=>  'settings_update',
								'register'				=> 	'mbr_member_registration',
								'email'					=>	'mbr_email_member',
								'send_email'			=>	'mbr_send_email',
								'aim'					=>	'mbr_aim_console',
								'icq'					=>	'mbr_icq_console',
								'profile_main'			=>	'mbr_my_account',
								'edit_profile'			=>	'mbr_edit_your_profile',
								'edit_email'			=>	'email_settings',
								'edit_userpass'			=>	'username_and_password',
								'edit_localization'		=>	'localization_settings',
								'edit_subscriptions'	=>	'subscriptions',
								'edit_ignore_list'		=>	'ignore_list',
								'edit_notepad'			=>	'notepad',
								'edit_avatar'			=>	'edit_avatar',
								'edit_photo'			=>	'edit_photo',
								'edit_preferences'		=>	'edit_preferences',
								'update_preferences'	=> 	'update_preferences',
								'upload_photo'			=>	'update_photo',
								'browse_avatars'		=>	'browse_avatars',
								'update_profile'		=>	'profile_updated',
								'update_email'			=>	'mbr_email_updated',
								'update_userpass'		=>	'username_and_password',
								'update_localization'	=>	'localization_settings',
								'update_subscriptions'	=>	'subscription_manager',
								'update_ignore_list'	=>	'ignore_list',
								'update_notepad'		=>	'notepad',
								'select_avatar'			=>	'update_avatar',
								'upload_avatar'			=>	'upload_avatar',
								'update_avatar'			=>	'update_avatar',
								'pm_view'				=>	'private_messages',
								'pm'					=>	'compose_message',
								'view_folder'			=>  'view_folder',
								'view_message'			=>	'view_message',
								'edit_signature'		=>	'edit_signature',
								'update_signature'		=>  'update_signature',
								'compose'				=> 	'compose_message',
								'deleted'				=> 	'deleted_messages',
								'folders'				=>	'edit_folders',
								'buddies'				=>	'buddy_list',
								'blocked'				=>	'blocked_list',
								'edit_folders'			=>  'edit_folders',
								'inbox'					=>  'view_folder',
								'edit_list'				=>  'edit_list',
								'send_message'			=>  'view_folder',
								'modify_messages'		=>  'private_messages',
								'bulletin_board'		=>	'bulletin_board',
								'send_bulletin'			=>  'send_bulletin',
								'sending_bulletin'		=>	'sending_bulletin'
								);


	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		ee()->lang->loadfile('myaccount');
		ee()->lang->loadfile('member');
		ee()->functions->template_type = 'webpage';
		ee()->db->cache_off();
		$this->trigger = ee()->config->item('profile_trigger');
	}

	// --------------------------------------------------------------------

	/**
	 * Prep the Request String
	 */
	public function _prep_request()
	{
		// Typcially the profile page URLs will be something like:
		//
		// index.php/member/123/
		// index.php/member/memberlist/
		// index.php/member/profile/
		// etc...
		//
		// The second segment will be assigned to the $this->request variable.
		// This determines what page is shown. Anything after that will normally
		// be an ID number, so we'll assign it to the $this->cur_id variable.

		$this->request = trim_slashes(ee()->uri->uri_string);

		if (FALSE !== ($pos = strpos($this->request, $this->trigger.'/')))
		{
			$this->request = substr($this->request, $pos);
		}

		if (preg_match("#/simple#", $this->request))
		{
			$this->request = str_replace("/simple", '', $this->request);
			$this->show_headings = FALSE;
		}

		if ($this->request == $this->trigger)
		{
			$this->request = '';
		}
		elseif (strpos($this->request, '/') !== FALSE)
		{
			$xr = explode("/", $this->request);
			$this->request = str_replace(current($xr).'/', '', $this->request);
		}

		// Determine the ID number, if any
		$this->cur_id = '';

		if (strpos($this->request, '/') !== FALSE)
		{
			$x = explode("/", $this->request);

			if (count($x) > 2)
			{
				$this->request		= $x[0];
				$this->cur_id		= $x[1];
				$this->uri_extra	= $x[2];
			}
			else
			{
				$this->request		= $x[0];
				$this->cur_id		= $x[1];
			}
		}

		// Is this a public profile request?
		// Public member profiles are found at:
		//
		// index.php/member/123/
		//
		// Since the second segment contains a number instead of the
		// normal text string we know it's a public profile request.
		// We'll do a little reassignment...

 		if (is_numeric($this->request))
 		{
 			$this->cur_id	= $this->request;
 			$this->request	= 'public_profile';
 		}

		if ($this->request == '')
		{
 			$this->request	= 'public_profile';
		}

		// Disable the full page view
 		if (in_array($this->request, $this->simple_page))
 		{
			$this->show_headings = FALSE;
 		}

 		if (in_array($this->request, $this->no_breadcrumb))
 		{
			$this->breadcrumb = FALSE;
 		}


 		// Validate ID number
		// The $this->cur_id variable can only contain a number.
		// There are a few exceptions like the memberlist page and the
		// subscriptions page

 		if ( ! in_array($this->request, $this->id_override) &&
 			$this->cur_id != '' && ! is_numeric($this->cur_id))
 		{
 			return FALSE;
 		}

 		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Run the Member Class
	 */
	public function manager()
	{
		// Prep the request
		if ( ! $this->_prep_request())
		{
			$this->_show_404_template();
		}

		// -------------------------------------------
		// 'member_manager' hook.
		//  - Seize control over any Member Module user side request
		//  - Added: 1.5.2
		//
			if (ee()->extensions->active_hook('member_manager') === TRUE)
			{
				$edata = ee()->extensions->universal_call('member_manager', $this);
				if (ee()->extensions->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------

		// Is the user logged in?
		if ($this->request != 'login' &&
			! in_array($this->request, $this->no_login) &&
			ee()->session->userdata('member_id') == 0)
		{
			return $this->_final_prep($this->profile_login_form('self'));
 		}

 		// Left-side Menu
		$left = ( ! in_array($this->request, $this->no_menu)) ? $this->profile_menu() : '';

		// Validate the request
		$methods = array(
			'public_profile',
			'memberlist',
			'member_search',
			'do_member_search',
			'login',
			'unpw_update',
			'register',
			'profile',
			'edit_preferences',
			'update_preferences',
			'edit_profile',
			'update_profile',
			'edit_email',
			'update_email',
			'edit_userpass',
			'update_userpass',
			'edit_localization',
			'update_localization',
			'edit_notepad',
			'update_notepad',
			'edit_signature',
			'update_signature',
			'edit_avatar',
			'browse_avatars',
			'select_avatar',
			'upload_avatar',
			'edit_photo',
			'upload_photo',
			'edit_subscriptions',
			'update_subscriptions',
			'edit_ignore_list',
			'update_ignore_list',
			'member_mini_search',
			'do_member_mini_search',
			'email_console',
			'aim_console',
			'icq_console',
			'send_email',
			'forgot_password',
			'reset_password',
			'smileys',
			'messages',
			'delete'
		);


		if ( ! in_array($this->request, $methods))
		{
			$this->_show_404_template();
		}

		// Call the requested function
		if ($this->request == 'profile') $this->request = 'profile_main';
		if ($this->request == 'register') $this->request = 'registration_form';
		if ($this->cur_id  == 'member_search') {$left = ''; $this->breadcrumb = FALSE; $this->show_headings = FALSE;}
		if ($this->cur_id  == 'do_member_search') {$left = ''; $this->breadcrumb = FALSE; $this->show_headings = FALSE;}
		if ($this->cur_id  == 'buddy_search') {$left = ''; $this->breadcrumb = FALSE; $this->show_headings = FALSE;}
		if ($this->cur_id  == 'do_buddy_search') {$left = ''; $this->breadcrumb = FALSE; $this->show_headings = FALSE;}

		$function = $this->request;

		if (in_array($function, array('upload_photo', 'upload_avatar', 'upload_signature_image', '_upload_image')))
		{
			require_once PATH_MOD.'member/mod.member_images.php';

			$MI = new Member_images();

			foreach(get_object_vars($this) as $key => $value)
			{
				$MI->{$key} = $value;
			}

			$content = $MI->$function();
		}
		else
		{
			$content = $this->$function();
		}

		if ($this->cur_id  == 'edit_folders')	{$left = $this->profile_menu();}
		if ($this->cur_id  == 'send_message')	{$left = $this->profile_menu();}

		// Parse the template the template
		if ($left == '')
		{
			$out = $this->_var_swap($this->_load_element('basic_profile'),
									array(
											'include:content'	=> $content
										 )
									 );
		}
		else
		{
			$out = $this->_var_swap($this->_load_element('full_profile'),
									array(
											'include:menu'		=> $left,
											'include:content'	=> $content
										 )
									 );
		}

		// Output the finalized request
		return $this->_final_prep($out);
	}

	// --------------------------------------------------------------------

	/**
	 * Private Messages
	 */
	public function messages()
	{
		if ((ee()->session->userdata('can_send_private_messages') != 'y' &&
			ee()->session->userdata('group_id') != '1') OR
			ee()->session->userdata('accept_messages') != 'y')
		{
			return $this->profile_main();
		}

		if ( ! class_exists('EE_Messages'))
		{
			require APPPATH.'libraries/Messages.php';
		}

		$MESS = new EE_Messages;
		$MESS->base_url = $this->_member_path('messages').'/';
		$MESS->allegiance = 'user';
		$MESS->theme_path = $this->theme_path;
		$MESS->request = $this->cur_id;
		$MESS->cur_id = $this->uri_extra;
		$MESS->MS =& $this;
		$MESS->manager();

		$this->page_title = $MESS->title;
		$this->head_extra = $MESS->header_javascript;
		return $MESS->return_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Member Profile - Menu
	 */
	public function profile_menu()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->profile_menu();
	}

	// --------------------------------------------------------------------

	/**
	 * Private Messages - Menu
	 */
	public function pm_menu()
	{
		if ((ee()->session->userdata('can_send_private_messages') != 'y' &&
			ee()->session->userdata('group_id') != '1') OR
			ee()->session->userdata('accept_messages') != 'y')
		{
			return;
		}

		if ( ! class_exists('EE_Messages'))
		{
			require APPPATH.'libraries/Messages.php';
		}

		$MESS = new EE_Messages;
		$MESS->base_url = $this->_member_path('messages');
		$MESS->allegiance  = 'user';
		$MESS->theme_path = $this->theme_path;
		$MESS->MS =& $this;

		$MESS->create_menu();
		return $MESS->menu;
	}

	// --------------------------------------------------------------------

	/**
	 * Member Profile Main Page
	 */
	public function profile_main()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->profile_main();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Public Profile
	 */
	public function public_profile()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->public_profile();
	}

	// --------------------------------------------------------------------

	/**
	 * Login Page
	 */
	public function profile_login_form($return = '-2')
	{
		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		$MA = new Member_auth();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MA->{$key} = $value;
		}

		return $MA->profile_login_form($return);
	}

	// --------------------------------------------------------------------

	/**
	 * Member Profile Edit Page
	 */
	public function edit_profile()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_profile();
	}

	// --------------------------------------------------------------------

	/**
	 * Profile Update
	 */
	public function update_profile()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_profile();
	}

	// --------------------------------------------------------------------

	/**
	 * Forum Preferences
	 */
	public function edit_preferences()
	{
	 	if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_preferences();
	}

	// --------------------------------------------------------------------

	/**
	 * Update Preferences
	 */
	public function update_preferences()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_preferences();
	}

	// --------------------------------------------------------------------

	/**
	 * Email Settings
	 */
	public function edit_email()
	{
	 	if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_email();
	}

	// --------------------------------------------------------------------

	/**
	 * Email Update
	 */
	public function update_email()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_email();
	}

	// --------------------------------------------------------------------

	/**
	 * Username/Password Preferences
	 */
	public function edit_userpass()
	{
	 	if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_userpass();
	}

	// --------------------------------------------------------------------

	/**
	 * Username/Password Update
	 */
	public function update_userpass()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_userpass();
	}

	// --------------------------------------------------------------------

	/**
	 * Localization Edit Form
	 */
	public function edit_localization()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_localization();
	}

	// --------------------------------------------------------------------

	/**
	 * Update Localization Prefs
	 */
	public function update_localization()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_localization();
	}

	// --------------------------------------------------------------------

	/**
	 * Signature Edit Form
	 */
	public function edit_signature()
	{
		if ( ! class_exists('Member_images'))
		{
			require PATH_MOD.'member/mod.member_images.php';
		}

		$MI = new Member_images();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MI->{$key} = $value;
		}

		return $MI->edit_signature();
	}

	// --------------------------------------------------------------------

	/**
	 * Update Signature
	 */
	public function update_signature()
	{
		if ( ! class_exists('Member_images'))
		{
			require PATH_MOD.'member/mod.member_images.php';
		}

		$MI = new Member_images();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MI->{$key} = $value;
		}

		return $MI->update_signature();
	}

	// --------------------------------------------------------------------

	/**
	 * Avatar Edit Form
	 */
	public function edit_avatar()
	{
		if ( ! class_exists('Member_images'))
		{
			require PATH_MOD.'member/mod.member_images.php';
		}

		$MI = new Member_images();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MI->{$key} = $value;
		}

		return $MI->edit_avatar();
	}

	// --------------------------------------------------------------------

	/**
	 * Browse Avatars
	 */
	public function browse_avatars()
	{
		if ( ! class_exists('Member_images'))
		{
			require PATH_MOD.'member/mod.member_images.php';
		}

		$MI = new Member_images();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MI->{$key} = $value;
		}

		return $MI->browse_avatars();
	}

	// --------------------------------------------------------------------

	/**
	 * Select Avatar From Library
	 */
	public function select_avatar()
	{
		if ( ! class_exists('Member_images'))
		{
			require PATH_MOD.'member/mod.member_images.php';
		}

		$MI = new Member_images();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MI->{$key} = $value;
		}

		return $MI->select_avatar();
	}

	// --------------------------------------------------------------------

	/**
	 * Photo Edit Form
	 */
	public function edit_photo()
	{
		if ( ! class_exists('Member_images'))
		{
			require PATH_MOD.'member/mod.member_images.php';
		}

		$MI = new Member_images();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MI->{$key} = $value;
		}

		return $MI->edit_photo();
	}

	// --------------------------------------------------------------------

	/**
	 * Notepad Edit Form
	 */
	public function edit_notepad()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_notepad();
	}

	// --------------------------------------------------------------------

	/**
	 * Update Notepad
	 */
	public function update_notepad()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_notepad();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Login
	 */
	public function member_login()
	{
		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		$MA = new Member_auth();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MA->{$key} = $value;
		}

		$MA->member_login();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Logout
	 */
	public function member_logout()
	{
		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		$MA = new Member_auth();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MA->{$key} = $value;
		}

		$MA->member_logout();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Forgot Password Form
	 */
	public function forgot_password($ret = '-3')
	{
		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		$MA = new Member_auth();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MA->{$key} = $value;
		}

		return $MA->forgot_password($ret);
	}

	// --------------------------------------------------------------------

	/**
	 * Retreive Forgotten Password
	 */
	public function send_reset_token()
	{
		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		$MA = new Member_auth();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MA->{$key} = $value;
		}

		$MA->send_reset_token();
	}

	// --------------------------------------------------------------------

	/**
	 * Reset the user's password
	 */
	public function reset_password()
	{
		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		$MA = new Member_auth();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MA->{$key} = $value;
		}

		return $MA->reset_password();
	}

	// --------------------------------------------------------------------

	/**
	 *
	 */
	public function process_reset_password()
	{
		if ( ! class_exists('Member_auth'))
		{
			require PATH_MOD.'member/mod.member_auth.php';
		}

		$MA = new Member_auth();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MA->{$key} = $value;
		}

		return $MA->process_reset_password();
	}

	// --------------------------------------------------------------------

	/**
	 * Subscriptions Edit Form
	 */
	public function edit_subscriptions()
	{
		if ( ! class_exists('Member_subscriptions'))
		{
			require PATH_MOD.'member/mod.member_subscriptions.php';
		}

		$MS = new Member_subscriptions();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_subscriptions();
	}

	// --------------------------------------------------------------------

	/**
	 * Update Subscriptions
	 */
	public function update_subscriptions()
	{
		if ( ! class_exists('Member_subscriptions'))
		{
			require PATH_MOD.'member/mod.member_subscriptions.php';
		}

		$MS = new Member_subscriptions();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_subscriptions();
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Ignore List Form
	 */
	public function edit_ignore_list()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->edit_ignore_list();
	}

	// --------------------------------------------------------------------

	/**
	 * Update Ignore List
	 */
	public function update_ignore_list()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->update_ignore_list();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Mini Search
	 */
	public function member_mini_search()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		$this->_set_page_title(ee()->lang->line('member_search'));
		return $MS->member_mini_search();
	}

	// --------------------------------------------------------------------

	/**
	 * Do Member Mini Search
	 */
	public function do_member_mini_search()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		$this->_set_page_title(ee()->lang->line('member_search'));
		return $MS->do_member_mini_search();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Registration Form
	 */
	public function registration_form()
	{
		if ( ! class_exists('Member_register'))
		{
			require PATH_MOD.'member/mod.member_register.php';
		}

		$MR = new Member_register();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MR->{$key} = $value;
		}

		return $MR->registration_form();
	}

	// --------------------------------------------------------------------

	/**
	 * Register Member
	 */
	public function register_member()
	{
		if ( ! class_exists('Member_register'))
		{
			require PATH_MOD.'member/mod.member_register.php';
		}

		$MR = new Member_register();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MR->{$key} = $value;
		}

		$MR->register_member();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Self-Activation
	 */
	public function activate_member()
	{
		if ( ! class_exists('Member_register'))
		{
			require PATH_MOD.'member/mod.member_register.php';
		}

		$MR = new Member_register();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MR->{$key} = $value;
		}

		$MR->activate_member();
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Page
	 */
	public function delete()
	{
		return $this->confirm_delete_form();
	}

	// --------------------------------------------------------------------

	/**
	 * Self-delete confirmation form
	 */
	public function confirm_delete_form()
	{
		if (ee()->session->userdata('can_delete_self') !== 'y')
		{
			return ee()->output->show_user_error('general', ee()->lang->line('cannot_delete_self'));
		}
		else
		{
			$delete_form = $this->_load_element('delete_confirmation_form');

			$data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id('Member', 'member_delete');
			$data['onsubmit'] = "if( ! confirm('{lang:final_delete_confirm}')) return false;";
			$data['id']	  = 'member_delete_form';

			$this->_set_page_title(ee()->lang->line('member_delete'));

			return $this->_var_swap($delete_form, array('form_declaration' => ee()->functions->form_declaration($data)));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Member self-delete
	 */
	public function member_delete()
	{
		// Make sure they got here via a form
		if ( ! ee()->input->post('ACT'))
		{
			// No output for you, Mr. URL Hax0r
			return FALSE;
		}

		ee()->lang->loadfile('login');

		// No sneakiness - we'll do this in case the site administrator
		// has foolishly turned off secure forms and some monkey is
		// trying to delete their account from an off-site form or
		// after logging out.

		if (ee()->session->userdata('member_id') == 0 OR
			ee()->session->userdata('can_delete_self') !== 'y')
		{
			return ee()->output->show_user_error('general', ee()->lang->line('not_authorized'));
		}

		// If the user is a SuperAdmin, then no deletion
		if (ee()->session->userdata('group_id') == 1)
		{
			return ee()->output->show_user_error('general', ee()->lang->line('cannot_delete_super_admin'));
		}

		// Is IP and User Agent required for login?  Then, same here.
		if (ee()->config->item('require_ip_for_login') == 'y')
		{
			if (ee()->session->userdata('ip_address') == '' OR
				ee()->session->userdata('user_agent') == '')
			{
				return ee()->output->show_user_error('general', ee()->lang->line('unauthorized_request'));
				}
		}

		// Check password lockout status
		if (ee()->session->check_password_lockout(ee()->session->userdata('username')) === TRUE)
		{
			ee()->lang->loadfile('login');

			return ee()->output->show_user_error(
				'general',
				sprintf(lang('password_lockout_in_effect'), ee()->config->item('password_lockout_interval'))
			);
		}

		// Are you who you say you are, or someone sitting at someone
		// else's computer being mean?!
		ee()->load->library('auth');

		if ( ! ee()->auth->authenticate_id(ee()->session->userdata('member_id'),
											 	ee()->input->post('password')))
		{
			ee()->session->save_password_lockout(ee()->session->userdata('username'));

			return ee()->output->show_user_error('general', ee()->lang->line('invalid_pw'));
		}

		// No turning back, get to deletin'!
		ee()->load->model('member_model');
		ee()->member_model->delete_member(ee()->session->userdata('member_id'));

		// Email notification recipients
		if (ee()->session->userdata('mbr_delete_notify_emails') != '')
		{

			$notify_address = ee()->session->userdata('mbr_delete_notify_emails');

			$swap = array(
				'name'		=> ee()->session->userdata('screen_name'),
				'email'		=> ee()->session->userdata('email'),
				'site_name'	=> stripslashes(ee()->config->item('site_name'))
			);

			$email_subject = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_title'), $swap);
			$email_msg = ee()->functions->var_swap(ee()->lang->line('mbr_delete_notify_message'), $swap);

			// No notification for the user themselves, if they're in the list
			if (strpos($notify_address, ee()->session->userdata('email')) !== FALSE)
			{
				$notify_address = str_replace(ee()->session->userdata('email'), "", $notify_address);
			}

			// Remove multiple commas
			$notify_address = reduce_multiples($notify_address, ',', TRUE);

			if ($notify_address != '')
			{
				// Send email
				ee()->load->library('email');

				// Load the text helper
				ee()->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					ee()->email->EE_initialize();
					ee()->email->wordwrap = FALSE;
					ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
					ee()->email->to($addy);
					ee()->email->reply_to(ee()->config->item('webmaster_email'));
					ee()->email->subject($email_subject);
					ee()->email->message(entities_to_ascii($email_msg));
					ee()->email->send();
				}
			}
		}

		ee()->db->where('session_id', ee()->session->userdata('session_id'))
					 ->delete('sessions');

		ee()->input->delete_cookie(ee()->session->c_session);
		ee()->input->delete_cookie(ee()->session->c_expire);
		ee()->input->delete_cookie(ee()->session->c_anon);
		ee()->input->delete_cookie('read_topics');
		ee()->input->delete_cookie('tracker');

		// Build Success Message
		$url	= ee()->config->item('site_url');
		$name	= stripslashes(ee()->config->item('site_name'));

		$data = array(	'title' 	=> ee()->lang->line('mbr_delete'),
						'heading'	=> ee()->lang->line('thank_you'),
						'content'	=> ee()->lang->line('mbr_account_deleted'),
						'redirect'	=> '',
						'link'		=> array($url, $name)
					 );

		ee()->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Login Page
	 */
	public function login()
	{
		return $this->profile_login_form();
	}

	// --------------------------------------------------------------------

	/**
	 * Manual Login Form
	 *
	 * This lets users create a stand-alone login form in any template
	 */
	public function login_form()
	{
		if (ee()->config->item('website_session_type') != 'c')
		{
			ee()->TMPL->tagdata = preg_replace("/{if\s+auto_login}.*?{".'\/'."if}/s", '', ee()->TMPL->tagdata);
		}
		else
		{
			ee()->TMPL->tagdata = preg_replace("/{if\s+auto_login}(.*?){".'\/'."if}/s", "\\1", ee()->TMPL->tagdata);
		}

		// Create form
		$data['hidden_fields'] = array(
										'ACT' => ee()->functions->fetch_action_id('Member', 'member_login'),
										'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-2'
									  );

		if (ee()->TMPL->fetch_param('name') !== FALSE &&
			preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'), $match))
		{
			$data['name'] = ee()->TMPL->fetch_param('name');
			ee()->TMPL->log_item('Member Login Form:  The \'name\' parameter has been deprecated.  Please use form_name');
		}
		elseif (ee()->TMPL->fetch_param('form_name') && ee()->TMPL->fetch_param('form_name') != "")
		{
			$data['name'] = ee()->TMPL->fetch_param('form_name');
		}

		if (ee()->TMPL->fetch_param('id') !== FALSE &&
			preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('id')))
		{
			$data['id'] = ee()->TMPL->fetch_param('id');
			ee()->TMPL->log_item('Member Login Form:  The \'id\' parameter has been deprecated.  Please use form_id');
		}
		else
		{
			$data['id'] = ee()->TMPL->form_id;
		}

		$data['class'] = ee()->TMPL->form_class;

		$data['action'] = ee()->TMPL->fetch_param('action');

		$res  = ee()->functions->form_declaration($data);

		$res .= stripslashes(ee()->TMPL->tagdata);

		$res .= "</form>";

		return $res;
	}

	// --------------------------------------------------------------------

	/**
	 * Username/password update
	 */
	public function unpw_update()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		return $MS->unpw_update();
	}

	// --------------------------------------------------------------------

	/**
	 * Update the username/password
	 */
	public function update_un_pw()
	{
		if ( ! class_exists('Member_settings'))
		{
			require PATH_MOD.'member/mod.member_settings.php';
		}

		$MS = new Member_settings();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}

		$MS->update_un_pw();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Email Form
	 */
	public function email_console()
	{
		if ( ! class_exists('Member_memberlist'))
		{
			require PATH_MOD.'member/mod.member_memberlist.php';
		}

		$MM = new Member_memberlist();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MM->{$key} = $value;
		}

		return $MM->email_console();
	}

	// --------------------------------------------------------------------

	/**
	 * Send Member Email
	 */
	public function send_email()
	{
		if ( ! class_exists('Member_memberlist'))
		{
			require PATH_MOD.'member/mod.member_memberlist.php';
		}

		$MM = new Member_memberlist();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MM->{$key} = $value;
		}

		return $MM->send_email();
	}

	// --------------------------------------------------------------------

	/**
	 * AIM Console
	 */
	public function aim_console()
	{
		if ( ! class_exists('Member_memberlist'))
		{
			require PATH_MOD.'member/mod.member_memberlist.php';
		}

		$MM = new Member_memberlist();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MM->{$key} = $value;
		}

		return $MM->aim_console();
	}

	// --------------------------------------------------------------------

	/**
	 * ICQ Console
	 */
	public function icq_console()
	{
		if ( ! class_exists('Member_memberlist'))
		{
			require PATH_MOD.'member/mod.member_memberlist.php';
		}

		$MM = new Member_memberlist();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MM->{$key} = $value;
		}

		return $MM->icq_console();
	}

	// --------------------------------------------------------------------

	/**
	 * Member List
	 */
	public function memberlist()
	{
		if ( ! class_exists('Member_memberlist'))
		{
			require PATH_MOD.'member/mod.member_memberlist.php';
		}

		$MM = new Member_memberlist();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MM->{$key} = $value;
		}

		return $MM->memberlist();
	}

	// --------------------------------------------------------------------

	/**
	 * Member Search Results
	 */
	public function member_search()
	{
		if ( ! class_exists('Member_memberlist'))
		{
			require PATH_MOD.'member/mod.member_memberlist.php';
		}

		$MM = new Member_memberlist();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MM->{$key} = $value;
		}

		return $MM->memberlist();
	}

	// --------------------------------------------------------------------

	/**
	 * Do A Member Search
	 */
	public function do_member_search()
	{
		if ( ! class_exists('Member_memberlist'))
		{
			require PATH_MOD.'member/mod.member_memberlist.php';
		}

		$MM = new Member_memberlist();

		foreach(get_object_vars($this) as $key => $value)
		{
			$MM->{$key} = $value;
		}

		return $MM->do_member_search();
	}

	// --------------------------------------------------------------------

	/**
	 * Emoticons
	 */
	public function smileys()
	{
		if (ee()->session->userdata('member_id') == 0)
		{
			return ee()->output->fatal_error(ee()->lang->line('must_be_logged_in'));
		}

		$class_path = PATH_MOD.'emoticon/emoticons.php';

		if ( ! is_file($class_path) OR ! @include_once($class_path))
		{
			return ee()->output->fatal_error('Unable to locate the smiley images');
		}

		if ( ! is_array($smileys))
		{
			return;
		}

		$path = ee()->config->slash_item('emoticon_url');

		ob_start();
		?>
		<script type="text/javascript">
		<!--

		function add_smiley(smiley)
		{
			var el = opener.document.getElementById('submit_post').body;

			if ('selectionStart' in el) {
				newStart = el.selectionStart + smiley.length;

				el.value = el.value.substr(0, el.selectionStart) +
								smiley +
								el.value.substr(el.selectionEnd, el.value.length);
				el.setSelectionRange(newStart, newStart);
			}
			else if (opener.document.selection) {
				el.focus();
				opener.document.selection.createRange().text = smiley;
			}
			else {
				el.value += " " + smiley + " ";
			}

			el.focus();
			window.close();
		}

		//-->
		</script>

		<?php

		$javascript = ob_get_contents();
		ob_end_clean();
		$r = $javascript;


		$i = 1;

		$dups = array();

		foreach ($smileys as $key => $val)
		{
			if ($i == 1)
			{
				$r .= "<tr>\n";
			}

			if (in_array($smileys[$key]['0'], $dups))
				continue;

			$r .= "<td class='tableCellOne' align='center'><a href=\"#\" onclick=\"return add_smiley('".$key."');\"><img src=\"".$path.$smileys[$key]['0']."\" width=\"".$smileys[$key]['1']."\" height=\"".$smileys[$key]['2']."\" alt=\"".$smileys[$key]['3']."\" border=\"0\" /></a></td>\n";

			$dups[] = $smileys[$key]['0'];

			if ($i == 10)
			{
				$r .= "</tr>\n";

				$i = 1;
			}
			else
			{
				$i++;
			}
		}

		$r = rtrim($r);

		if (substr($r, -5) != "</tr>")
		{
			$r .= "</tr>\n";
		}

		$this->_set_page_title(ee()->lang->line('smileys'));
		return str_replace('{include:smileys}', $r, $this->_load_element('emoticon_page'));
	}

	// --------------------------------------------------------------------

	/**
	 * Convet special characters
	 */
	function _convert_special_chars($str)
	{
		return str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&apos;', '&quot;', '&#63;'), $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse the index template
	 */
	function _parse_index_template($str)
	{
		$req = ($this->request == '') ? 'profile' : $this->request;

		// We have to call this before putting it into the array
		$breadcrumb = $this->breadcrumb();

		return $this->_var_swap(ee()->TMPL->tagdata,
			array(
					'stylesheet'	=>	"<style type='text/css'>\n\n".$this->_load_element('stylesheet')."\n\n</style>",
					'javascript'	=>	$this->javascript,
					'heading'		=>	$this->page_title,
					'breadcrumb'	=>	$breadcrumb,
					'content'		=>	$str,
					'copyright'		=>	$this->_load_element('copyright')
				 )
			 );

	}

	// --------------------------------------------------------------------

	/**
	 * Member Home Page
	 */
	function _member_page($str)
	{
		$template = $this->_load_element('member_page');

		if ($this->show_headings == TRUE)
		{
			$template = $this->_allow_if('show_headings', $template);
		}
		else
		{
			$template = $this->_deny_if('show_headings', $template);
		}


		// We have to call this before putting it into the array
		$breadcrumb = $this->breadcrumb();

		$header = $this->_load_element('html_header');
		$css 	= $this->_load_element('stylesheet');

		$header = str_replace('{include:stylesheet}', $css, $header);
		$header = str_replace('{include:head_extra}', $this->head_extra, $header);

		return $this->_var_swap($template,
								array(

										'include:html_header'		=> $header,
										'include:page_header'		=> $this->_load_element('page_header'),
										'include:page_subheader'	=> $this->_load_element('page_subheader'),
										'include:member_manager'	=> $str,
										'include:breadcrumb'		=> $breadcrumb,
										'include:html_footer'		=> $this->_load_element('html_footer')
									 )
								);


	}

	// --------------------------------------------------------------------

	/**
	 * Load theme element
	 */
	function _load_element($which)
	{
		if ($this->theme_path == '')
		{
			$theme = (ee()->config->item('member_theme') == '') ? 'default' : ee()->config->item('member_theme');
			$this->theme_path = PATH_MBR_THEMES."{$theme}/";
		}

		if ( ! file_exists($this->theme_path.$which.'.html'))
		{
			$data = array(	'title' 	=> ee()->lang->line('error'),
							'heading'	=> ee()->lang->line('general_error'),
							'content'	=> ee()->lang->line('nonexistent_page'),
							'redirect'	=> '',
							'link'		=> array(ee()->config->item('site_url'), stripslashes(ee()->config->item('site_name')))
						 );

			set_status_header(404);
			return ee()->output->show_message($data, 0);
		}

		return $this->_prep_element(trim(file_get_contents($this->theme_path.$which.'.html')));
	}

	// --------------------------------------------------------------------

	/**
	 * Trigger Error Template
	 */
	function _trigger_error($heading, $message = '', $use_lang = TRUE)
	{
		return $this->_var_swap($this->_load_element('error'),
								array(
										'lang:heading'	=>	ee()->lang->line($heading),
										'lang:message'	=>	($use_lang == TRUE) ? ee()->lang->line($message) : $message
									 )
								);
	}

	// --------------------------------------------------------------------

	/**
	 * Sets the title of the page
	 */
	function _set_page_title($title)
	{
		if ($this->page_title == '')
		{
			$this->page_title = $title;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Member Breadcrumb
	 */
	public function breadcrumb()
	{
		if ($this->breadcrumb == FALSE)
		{
			return '';
		}

		$crumbs = $this->_crumb_trail(
										array(
												'link'	=> ee()->config->item('site_url'),
												'title'	=> stripslashes(ee()->config->item('site_name'))
											 )
									);

			if (ee()->uri->segment(2) == '')
			{
				return $this->_build_crumbs(ee()->lang->line('member_profile'), $crumbs, ee()->lang->line('member_profile'));
			}

			if (ee()->uri->segment(2) == 'messages')
			{
				$crumbs .= $this->_crumb_trail(array(
													'link' => $this->_member_path('/profile'),
													'title' => ee()->lang->line('control_panel_home')
													)
												);

				$pm_page =  (FALSE !== ($mbr_crumb = $this->_fetch_member_crumb(ee()->uri->segment(3)))) ? ee()->lang->line($mbr_crumb) : ee()->lang->line('view_folder');

				return $this->_build_crumbs($pm_page, $crumbs, $pm_page);
			}


			if (is_numeric(ee()->uri->segment(2)))
			{
				$query = ee()->db->query("SELECT screen_name FROM exp_members WHERE member_id = '".ee()->uri->segment(2)."'");

				$crumbs .= $this->_crumb_trail(array(
													'link' => $this->_member_path('/memberlist'),
													'title' => ee()->lang->line('mbr_memberlist')
													)
												);

				return $this->_build_crumbs($query->row('screen_name') , $crumbs, $query->row('screen_name') );
			}
			else
			{
				if (ee()->uri->segment(2) == 'memberlist')
				{
					return $this->_build_crumbs(ee()->lang->line('mbr_memberlist'), $crumbs, ee()->lang->line('mbr_memberlist'));
				}
				elseif (ee()->uri->segment(2) == 'member_search' OR ee()->uri->segment(2) == 'do_member_search')
				{
					return $this->_build_crumbs(ee()->lang->line('member_search'), $crumbs, ee()->lang->line('member_search'));
				}
				elseif (ee()->uri->segment(2) != 'profile' AND ! in_array(ee()->uri->segment(2), $this->no_menu))
				{
					$crumbs .= $this->_crumb_trail(array(
														'link' => $this->_member_path('/profile'),
														'title' => ee()->lang->line('control_panel_home')
														)
													);
				}

			}

			if (FALSE !== ($mbr_crumb = $this->_fetch_member_crumb(ee()->uri->segment(2))))
			{
				return $this->_build_crumbs(ee()->lang->line($mbr_crumb), $crumbs, ee()->lang->line($mbr_crumb));
			}
	}

	// --------------------------------------------------------------------

	/**
	 * Breadcrumb trail links
	 */
	function _crumb_trail($data)
	{
		$trail	= $this->_load_element('breadcrumb_trail');

		$crumbs = '';

		$crumbs .= $this->_var_swap($trail,
									array(
											'crumb_link'	=> $data['link'],
											'crumb_title'	=> $data['title']
											)
									);
		return $crumbs;
	}

	// --------------------------------------------------------------------

	/**
	 * Finalize the Crumbs
	 */
	function _build_crumbs($title, $crumbs, $str)
	{
		$this->_set_page_title(($title == '') ? 'Powered By ExpressionEngine' : $title);

		$crumbs .= str_replace('{crumb_title}', $str, $this->_load_element('breadcrumb_current_page'));

		$breadcrumb = $this->_load_element('breadcrumb');

		$breadcrumb = str_replace('{name}', ee()->session->userdata('screen_name'), $breadcrumb);

		return str_replace('{breadcrumb_links}', $crumbs, $breadcrumb);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch member profile crumb item
	 */
	function _fetch_member_crumb($item = '')
	{
		if ($item == '')
			return FALSE;

		return ( ! isset($this->crumb_map[$item])) ? FALSE : $this->crumb_map[$item];
	}

	// --------------------------------------------------------------------

	/**
	 * Create the "year" pull-down menu
	 */
	function _birthday_year($year = '')
	{
		$r = "<select name='bday_y' class='select'>\n";

		$selected = ($year == '') ? " selected='selected'" : '';

		$r .= "<option value=''{$selected}>".ee()->lang->line('year')."</option>\n";

		for ($i = date('Y', ee()->localize->now); $i > 1904; $i--)
		{
			$selected = ($year == $i) ? " selected='selected'" : '';

			$r .= "<option value='{$i}'{$selected}>".$i."</option>\n";
		}

		$r .= "</select>\n";

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Create the "month" pull-down menu
	 */
	function _birthday_month($month = '')
	{
		$months = array('01' => 'January','02' => 'February','03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December');

		$r = "<select name='bday_m' class='select'>\n";

		$selected = ($month == '') ? " selected='selected'" : '';

		$r .= "<option value=''{$selected}>".ee()->lang->line('month')."</option>\n";

		for ($i = 1; $i < 13; $i++)
		{
			if (strlen($i) == 1)
				$i = '0'.$i;

			$selected = ($month == $i) ? " selected='selected'" : '';

			$r .= "<option value='{$i}'{$selected}>".ee()->lang->line($months[$i])."</option>\n";
		}

		$r .= "</select>\n";

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Create the "day" pull-down menu
	 */
	function _birthday_day($day = '')
	{
		$r = "<select name='bday_d' class='select'>\n";

		$selected = ($day == '') ? " selected='selected'" : '';

		$r .= "<option value=''{$selected}>".ee()->lang->line('day')."</option>\n";

		for ($i = 1; $i <= 31; $i++)
		{
			$selected = ($day == $i) ? " selected='selected'" : '';

			$r .= "<option value='{$i}'{$selected}>".$i."</option>\n";
		}

		$r .= "</select>\n";

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Element Data
	 *
	 * Right now we only use this to parse the logged-in/logged-out vars
	 */
	function _prep_element($str)
	{
		if ($str == '')
		{
			return '';
		}

		if (ee()->session->userdata('member_id') == 0)
		{
			$str = $this->_deny_if('logged_in', $str);
			$str = $this->_allow_if('logged_out', $str);
		}
		else
		{
			$str = $this->_allow_if('logged_in', $str);
			$str = $this->_deny_if('logged_out', $str);
		}

		// Parse the forum conditional
		if (ee()->config->item('forum_is_installed') == "y")
		{
			$str = $this->_allow_if('forum_installed', $str);
		}
		else
		{
			$str = $this->_deny_if('forum_installed', $str);
		}

		// Parse the self deletion conditional
		if (ee()->session->userdata('can_delete_self') == 'y' &&
			ee()->session->userdata('group_id') != 1)
		{
			$str = $this->_allow_if('can_delete', $str);
		}
		else
		{
			$str = $this->_deny_if('can_delete', $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Finalize a few things
	 */
	function _final_prep($str)
	{
		// Which mode are we in?
		// This class can either be run in "stand-alone" mode or through the template engine.
		$template_parser = FALSE;

		if (class_exists('Template'))
		{
			if (ee()->TMPL->tagdata != '')
			{
				$str = $this->_parse_index_template($str);
				$template_parser = TRUE;
				ee()->TMPL->disable_caching = TRUE;
			}
		}

		if ($template_parser == FALSE AND $this->in_forum == FALSE)
		{
			$str = $this->_member_page($str);
		}

		// Parse the language text
		if (preg_match_all("/{lang:(.+?)\}/i", $str, $matches))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$line = (ee()->lang->line($matches['1'][$j]) == $matches['1'][$j]) ? ee()->lang->line('mbr_'.$matches['1'][$j]) : ee()->lang->line($matches['1'][$j]);

				$str = str_replace($matches['0'][$j], $line, $str);
			}
		}

		// Parse old style path variables
		// This is here for backward compatibility for people with older templates
		$str = preg_replace_callback("/".LD."\s*path=(.*?)".RD."/", array(&ee()->functions, 'create_url'), $str);

		if (preg_match_all("#".LD."\s*(profile_path\s*=.*?)".RD."#", $str, $matches))
		{
			$i = 0;
			foreach ($matches['1'] as $val)
			{
				$path = ee()->functions->create_url(ee()->functions->extract_path($val).'/'.ee()->session->userdata('member_id'));
				$str = preg_replace("#".$matches['0'][$i++]."#", $path, $str, 1);
			}
		}
		// -------

		// Set some paths
		$theme_images = ee()->config->slash_item('theme_folder_url', 1).'profile_themes/'.ee()->config->item('member_theme').'/images/';

		if (ee()->session->userdata('profile_theme') != '')
		{
			$img_path = ee()->config->slash_item('theme_folder_url').'profile_themes/'.ee()->session->userdata('profile_theme').'/images/';
		}
		else
		{
			$img_path = ee()->config->slash_item('theme_folder_url', 1).'profile_themes/'.ee()->config->item('member_theme').'/images/';
		}

		$simple = ($this->show_headings == FALSE) ? '/simple' : '';

		if ($this->css_file_path == '')
		{
			$this->css_file_path = ee()->config->slash_item('theme_folder_url', 1).'profile_themes/'.ee()->config->item('member_theme').'profile.css';
		}

		// Parse {switch="foo|bar"} variables
		if (preg_match_all("/".LD."(switch\s*=.+?)".RD."/i", $str, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$sparam = ee()->functions->assign_parameters($match[1]);

				if (isset($sparam['switch']))
				{
					$sopt = explode("|", $sparam['switch']);

					$i = 1;
					while (($pos = strpos($str, LD.$match[1].RD)) !== FALSE)
					{
						$str = substr_replace($str, $sopt[($i++ + count($sopt) - 1) % count($sopt)], $pos, strlen(LD.$match[1].RD));
					}
				}
			}
		}

		// Finalize the output
		$str = ee()->functions->prep_conditionals($str, array('current_request' => $this->request));

		$str = $this->_var_swap($str,
								array(
										'lang'						=> ee()->config->item('xml_lang'),
										'charset'					=> ee()->config->item('output_charset'),
										'path:image_url'			=> ($this->image_url == '') ? $theme_images : $this->image_url,
										'path:your_control_panel'	=> $this->_member_path('profile'),
										'path:your_profile'			=> $this->_member_path(ee()->session->userdata('member_id')),
										'path:edit_preferences'		=> $this->_member_path('edit_preferences'),
										'path:register'				=> $this->_member_path('register'.$simple),
										'path:private_messages'		=> $this->_member_path('messages'),
										'path:memberlist'			=> $this->_member_path('memberlist'),
										'path:signature'			=> $this->_member_path('edit_signature'),
										'path:avatar'				=> $this->_member_path('edit_avatar'),
										'path:photo'				=> $this->_member_path('edit_photo'),
										'path:smileys'				=> $this->_member_path('smileys'),
										'path:forgot'				=> $this->_member_path('forgot_password'.$simple),
										'path:login'				=> $this->_member_path('login'.$simple),
										'path:delete'				=> $this->_member_path('delete'),
										'page_title'				=> $this->page_title,
										'site_name'					=> stripslashes(ee()->config->item('site_name')),
										'path:theme_css'			=> $this->css_file_path,
										'current_request'			=> $this->request
									)
								 );

		// parse regular global vars
		ee()->load->library('template', NULL, 'TMPL');

		// load up any Snippets
		ee()->db->select('snippet_name, snippet_contents');
		ee()->db->where('(site_id = '.ee()->db->escape_str(ee()->config->item('site_id')).' OR site_id = 0)');
		$fresh = ee()->db->get('snippets');

		if ($fresh->num_rows() > 0)
		{
			$snippets = array();

			foreach ($fresh->result() as $var)
			{
				$snippets[$var->snippet_name] = $var->snippet_contents;
			}

			ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $snippets);

			unset($snippets);
			unset($fresh);
		}

		if ( ! $this->in_forum)
		{
			ee()->TMPL->parse($str);
			$str = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
		}

		//  Add security hashes to forms
		if ( ! class_exists('Template'))
		{
			$str = ee()->functions->insert_action_ids(ee()->functions->add_form_security_hash($str));
		}

		$str = preg_replace("/".LD."if\s+.*?".RD.".*?".LD.'\/if'.RD."/s", "", $str);

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Set base values of class vars
	 */
	function _set_properties($props = array())
	{
		if (count($props) > 0)
		{
			foreach ($props as $key => $val)
			{
				$this->$key = $val;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Sets the member basepath
	 */
	function _member_set_basepath()
	{
		$this->basepath = ee()->functions->create_url($this->trigger);
	}

	// --------------------------------------------------------------------

	/**
	 * Compiles a path string
	 */
	function _member_path($uri = '')
	{
		if ($this->basepath == '')
		{
			$this->_member_set_basepath();
		}

		return reduce_double_slashes($this->basepath.'/'.$uri);
	}

	// --------------------------------------------------------------------

	/**
	 * Helpers for "if" conditions
	 */
	function _deny_if($cond, $str, $replace = '')
	{
		return preg_replace("/\{if\s+".$cond."\}.+?\{\/if\}/si", $replace, $str);
	}

	function _allow_if($cond, $str)
	{
		return preg_replace("/\{if\s+".$cond."\}(.+?)\{\/if\}/si", "\\1", $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace variables
	 */
	function _var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}

		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Swap single variables with final value
	 */
	function _var_swap_single($search, $replace, $source, $encode_ee_tags = TRUE)
	{
		if ($encode_ee_tags)
		{
			$replace = ee()->functions->encode_ee_tags($replace, TRUE);
		}

		return str_replace(LD.$search.RD, $replace, $source);
	}

	// --------------------------------------------------------------------

	/**
	 * Show 404 Template
	 *
	 * Show the real 404 template instead of an ACT error when we cannot
	 * find the page that was requested.
	 *
	 * @access protected
	 */
	protected function _show_404_template()
	{
		// 404 it
		ee()->load->library('template', NULL, 'TMPL');
		ee()->TMPL->show_404();
	}

	// --------------------------------------------------------------------

	/**
	 * Custom Member Profile Data
	 */
	function custom_profile_data()
	{

		$member_id = ( ! ee()->TMPL->fetch_param('member_id')) ? ee()->session->userdata('member_id') : ee()->TMPL->fetch_param('member_id');

		// Default Member Data
		ee()->db->select('m.member_id, m.group_id, m.username, m.screen_name, m.email, m.signature,
							m.avatar_filename, m.avatar_width, m.avatar_height,
							m.photo_filename, m.photo_width, m.photo_height,
							m.url, m.location, m.occupation, m.interests,
							m.bio,
							m.join_date, m.last_visit, m.last_activity, m.last_entry_date, m.last_comment_date,
							m.last_forum_post_date, m.total_entries, m.total_comments, m.total_forum_topics, m.total_forum_posts,
							m.language, m.timezone, m.bday_d, m.bday_m, m.bday_y, g.group_title');
		ee()->db->from(array('members m', 'member_groups g'));
		ee()->db->where('m.member_id', $member_id);
		ee()->db->where('g.site_id', ee()->config->item('site_id'));
		ee()->db->where('m.group_id = g.group_id');
		$query = ee()->db->get();

		if ($query->num_rows() == 0)
		{
			return ee()->TMPL->tagdata = '';
		}

		$default_fields = $query->row_array();

		// Is there an avatar?
		if (ee()->config->item('enable_avatars') == 'y' AND $query->row('avatar_filename') != '')
		{
			$avatar_path	= ee()->config->item('avatar_url').$query->row('avatar_filename');
			$avatar_width	= $query->row('avatar_width');
			$avatar_height	= $query->row('avatar_height');
			$avatar			= TRUE;
		}
		else
		{
			$avatar_path	= '';
			$avatar_width	= '';
			$avatar_height	= '';
			$avatar			= FALSE;
		}

		// Is there a member photo?
		if (ee()->config->item('enable_photos') == 'y' AND $query->row('photo_filename') != '')
		{
			$photo_path		= ee()->config->item('photo_url').$query->row('photo_filename');
			$photo_width	= $query->row('photo_width');
			$photo_height	= $query->row('photo_height');
			$photo			= TRUE;
		}
		else
		{
			$photo_path	= '';
			$photo_width	= '';
			$photo_height	= '';
			$photo			= FALSE;
		}

		// Is there a signature image?
		if (ee()->config->item('enable_signatures') == 'y' AND $query->row('sig_img_filename') != '')
		{
			$sig_img_path	= ee()->config->item('sig_img_url').$query->row('sig_img_filename');
			$sig_img_width	= $query->row('sig_img_width');
			$sig_img_height	= $query->row('sig_img_height');
			$sig_img_image	= TRUE;
		}
		else
		{
			$sig_img_path	= '';
			$sig_img_width	= '';
			$sig_img_height	= '';
			$sig_img		= FALSE;
		}

		// Parse variables
		if ($this->in_forum == TRUE)
		{
			$search_path = $this->forum_path.'member_search/'.$this->cur_id.'/';
		}
		else
		{
			$search_path = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->functions->fetch_action_id('Search', 'do_search').'&amp;mbr='.urlencode($query->row('member_id'));
		}

		$more_fields = array(
							'send_private_message'	=> $this->_member_path('messages/pm/'.$member_id),
							'search_path'			=> $search_path,
							'avatar_url'			=> $avatar_path,
							'avatar_filename'		=> $query->row('avatar_filename'),
							'avatar_width'			=> $avatar_width,
							'avatar_height'			=> $avatar_height,
							'photo_url'				=> $photo_path,
							'photo_filename'		=> $query->row('photo_filename'),
							'photo_width'			=> $photo_width,
							'photo_height'			=> $photo_height,
							'signature_image_url'		=> $sig_img_path,
							'signature_image_filename'	=> $query->row('sig_img_filename'),
							'signature_image_width'		=> $sig_img_width,
							'signature_image_height'	=> $sig_img_height
						);

		$default_fields = array_merge($default_fields, $more_fields);

		// Fetch the custom member field definitions
		$fields = array();

		ee()->db->select('m_field_id, m_field_name, m_field_fmt');
		$query = ee()->db->get('member_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$fields[$row['m_field_name']] = array($row['m_field_id'], $row['m_field_fmt']);
			}
		}

		ee()->db->where('member_id', $member_id);
		$query = ee()->db->get('member_data');

		if ($query->num_rows() == 0)
		{
			foreach ($fields as $key => $val)
			{
				ee()->TMPL->tagdata = ee()->TMPL->swap_var_single($key, '', ee()->TMPL->tagdata);
			}

			return ee()->TMPL->tagdata;
		}

		ee()->load->library('typography');
		ee()->typography->initialize();

		$cond = $default_fields;

		foreach ($query->result_array() as $row)
		{
			$cond['avatar']	= $avatar;
			$cond['photo'] = $photo;

			foreach($fields as $key =>  $value)
			{
				$cond[$key] = ee()->typography->parse_type($row['m_field_id_'.$value['0']],
												array(
													  'text_format'	=> $value['1'],
													  'html_format'	=> 'safe',
													  'auto_links'	=> 'y',
													  'allow_img_url' => 'n'
													 )
										  	  );
			}

			ee()->TMPL->tagdata = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $cond);

			// Swap Variables
			foreach (ee()->TMPL->var_single as $key => $val)
			{
				// parse default member data

				//  Format URLs
				if ($key == 'url')
				{
					if (substr($default_fields['url'], 0, 4) != "http" && strpos($default_fields['url'], '://') === FALSE)
					{
						$default_fields['url'] = "http://".$default_fields['url'];
					}
				}

				//  "last_visit"
				if (strncmp($key, 'last_visit', 10) == 0)
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_visit'] > 0) ? ee()->localize->format_date($val, $default_fields['last_visit']) : '', ee()->TMPL->tagdata);
				}

				//  "last_activity"
				if (strncmp($key, 'last_activity', 10) == 0)
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_activity'] > 0) ? ee()->localize->format_date($val, $default_fields['last_activity']) : '', ee()->TMPL->tagdata);
				}

				//  "join_date"
				if (strncmp($key, 'join_date', 9) == 0)
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['join_date'] > 0) ? ee()->localize->format_date($val, $default_fields['join_date']) : '', ee()->TMPL->tagdata);
				}

				//  "last_entry_date"
				if (strncmp($key, 'last_entry_date', 15) == 0)
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_entry_date'] > 0) ? ee()->localize->format_date($val, $default_fields['last_entry_date']) : '', ee()->TMPL->tagdata);
				}

				//  "last_forum_post_date"
				if (strncmp($key, 'last_forum_post_date', 20) == 0)
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_forum_post_date'] > 0) ? ee()->localize->format_date($val, $default_fields['last_forum_post_date']) : '', ee()->TMPL->tagdata);
				}

				//  parse "recent_comment"
				if (strncmp($key, 'last_comment_date', 17) == 0)
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_comment_date'] > 0) ? ee()->localize->format_date($val, $default_fields['last_comment_date']) : '', ee()->TMPL->tagdata);
				}

				//  {name}
				$name = ( ! $default_fields['screen_name']) ? $default_fields['username'] : $default_fields['screen_name'];

				$name = $this->_convert_special_chars($name);

				if ($key == "name")
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($val, $name, ee()->TMPL->tagdata);
				}

				//  {member_group}
				if ($key == "member_group")
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($val, $default_fields['group_title'], ee()->TMPL->tagdata);
				}

				//  {email}
				if ($key == "email")
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($val, ee()->typography->encode_email($default_fields['email']), ee()->TMPL->tagdata, FALSE);
				}

				//  {birthday}
				if ($key == "birthday")
				{
					$birthday = '';

					if ($default_fields['bday_m'] != '' AND $default_fields['bday_m'] != 0)
					{
						$month = (strlen($default_fields['bday_m']) == 1) ? '0'.$default_fields['bday_m'] : $default_fields['bday_m'];

						$m = ee()->localize->localize_month($month);

						$birthday .= ee()->lang->line($m['1']);

						if ($default_fields['bday_d'] != '' AND $default_fields['bday_d'] != 0)
						{
							$birthday .= ' '.$default_fields['bday_d'];
						}
					}

					if ($default_fields['bday_y'] != '' AND $default_fields['bday_y'] != 0)
					{
						if ($birthday != '')
						{
							$birthday .= ', ';
						}

						$birthday .= $default_fields['bday_y'];
					}

					if ($birthday == '')
					{
						$birthday = '';
					}

					ee()->TMPL->tagdata = $this->_var_swap_single($val, $birthday, ee()->TMPL->tagdata);
				}

				//  {timezone}
				if ($key == "timezone")
				{
					$timezone = ($default_fields['timezone'] != '') ? ee()->lang->line($default_fields['timezone']) : '';

					ee()->TMPL->tagdata = $this->_var_swap_single($val, $timezone, ee()->TMPL->tagdata);
				}

				//  {local_time}
				if (strncmp($key, 'local_time', 10) == 0)
				{
					$locale = FALSE;

					if (ee()->session->userdata('member_id') != $this->cur_id)
					{
						// Default is UTC?
						$locale = ($default_fields['timezone'] == '') ? 'UTC' : $default_fields['timezone'];
					}

					ee()->TMPL->tagdata = $this->_var_swap_single(
						$key,
						ee()->localize->format_date($val, NULL, $locale),
						ee()->TMPL->tagdata
					);
				}

				//  {bio}
				if ($key == 'bio')
				{
					$bio = ee()->typography->parse_type($default_fields[$val],
																 array(
																			'text_format'   => 'xhtml',
																			'html_format'   => 'safe',
																			'auto_links'    => 'y',
																			'allow_img_url' => 'n'
																	   )
																);

					ee()->TMPL->tagdata = $this->_var_swap_single($key, $bio, ee()->TMPL->tagdata);
				}

				// Special condideration for {total_forum_replies}, and
				// {total_forum_posts} whose meanings do not match the
				// database field names
				if ($key == 'total_forum_replies')
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($key, $default_fields['total_forum_posts'], ee()->TMPL->tagdata);
				}

				if ($key == 'total_forum_posts')
				{
					$total_posts = $default_fields['total_forum_topics'] + $default_fields['total_forum_posts'];
					ee()->TMPL->tagdata = $this->_var_swap_single($key, $total_posts, ee()->TMPL->tagdata);
				}

				// parse basic fields (username, screen_name, etc.)
				if (array_key_exists($key, $default_fields))
				{
					ee()->TMPL->tagdata = $this->_var_swap_single($val, $default_fields[$val], ee()->TMPL->tagdata);
				}

				// parse custom member fields
				if (isset($fields[$val]) && array_key_exists('m_field_id_'.$fields[$val]['0'], $row))
				{
					ee()->TMPL->tagdata = ee()->TMPL->swap_var_single(
														$val,
														ee()->typography->parse_type(
															$row['m_field_id_'.$fields[$val]['0']],
																				array(
																						'text_format'	=> $fields[$val]['1'],
																						'html_format'	=> 'safe',
																						'auto_links'	=> 'y',
																						'allow_img_url' => 'n'
																					  )
																			  ),
														ee()->TMPL->tagdata
													  );
				}
				//else { echo 'm_field_id_'.$fields[$val]['0']; }
			}
		}

		return ee()->TMPL->tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Ignore List
	 */
	function ignore_list()
	{
		$pre = 'ignore_';
		$prelen = strlen($pre);

		if ($member_id = ee()->TMPL->fetch_param('member_id'))
		{
			$query = ee()->db->query("SELECT ignore_list FROM exp_members WHERE member_id = '{$member_id}'");

			if ($query->num_rows() == 0)
			{
				return ee()->TMPL->no_results();
			}

			$ignored = ($query->row('ignore_list')  == '') ? array() : explode('|', $query->row('ignore_list') );
		}
		else
		{
			$ignored = ee()->session->userdata('ignore_list');
		}

		$query = ee()->db->query("SELECT m.member_id, m.group_id, m.username, m.screen_name, m.email, m.ip_address, m.location, m.total_entries, m.total_comments, m.private_messages, m.total_forum_topics, m.total_forum_posts AS total_forum_replies, m.total_forum_topics + m.total_forum_posts AS total_forum_posts,
							g.group_title AS group_description FROM exp_members AS m, exp_member_groups AS g
							WHERE g.group_id = m.group_id
							AND g.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'
							AND m.member_id IN ('".implode("', '", $ignored)."')");

		if ($query->num_rows() == 0)
		{
			return ee()->TMPL->no_results();
		}

		$tagdata = ee()->TMPL->tagdata;
		$out = '';

		foreach($query->result_array() as $row)
		{
			$temp = $tagdata;

			foreach (ee()->TMPL->var_single as $key => $val)
			{
				$val = substr($val, $prelen);

				if (isset($row[$val]))
				{
					$temp = ee()->TMPL->swap_var_single($pre.$val, ee()->functions->encode_ee_tags($row[$val]), $temp);
				}
			}

			$out .= $temp;
		}

		return ee()->TMPL->tagdata = $out;
	}
}
// END CLASS

/* End of file mod.member.php */
/* Location: ./system/expressionengine/modules/member/mod.member.php */
