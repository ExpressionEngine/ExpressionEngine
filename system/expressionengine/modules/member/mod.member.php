<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
						'aim_console', 'icq_console', 'forgot_password', 
						'delete', 'member_mini_search', 'do_member_mini_search'
					);

	var $no_login 			= array(
						'public_profile', 'memberlist', 'do_member_search', 
						'member_search', 'register', 'forgot_password', 'unpw_update'
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
	var $us_datecodes 		= array('long'	=>	'%F %d, %Y &nbsp;%h:%i %A');
	var $eu_datecodes 		= array('long'	=>	'%d %F, %Y &nbsp;%H:%i');
	var $crumb_map 			= array(
								'profile'				=>	'your_control_panel',
								'delete'				=>	'mbr_delete',
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

		$this->EE->lang->loadfile('myaccount');
		$this->EE->lang->loadfile('member');
		$this->EE->functions->template_type = 'webpage';
		$this->EE->db->cache_off();
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

		$this->EE->load->helper('string');

		$this->request = trim_slashes($this->EE->uri->uri_string);

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
			exit("Invalid Page Request");
		}

		// -------------------------------------------
		// 'member_manager' hook.
		//  - Seize control over any Member Module user side request
		//  - Added: 1.5.2
		//
			if ($this->EE->extensions->active_hook('member_manager') === TRUE)
			{
				$edata = $this->EE->extensions->universal_call('member_manager', $this);
				if ($this->EE->extensions->end_script === TRUE) return $edata;
			}
		//
		// -------------------------------------------

		// Is the user logged in?
		if ($this->request != 'login' && 
			! in_array($this->request, $this->no_login) && 
			$this->EE->session->userdata('member_id') == 0)
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
							'smileys',
							'messages',
							'delete'
						);


		if ( ! in_array($this->request, $methods))
		{
			return $this->EE->output->show_user_error('general', array($this->EE->lang->line('invalid_action')));
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
		if (($this->EE->session->userdata('can_send_private_messages') != 'y' && 
			$this->EE->session->userdata('group_id') != '1') OR 
			$this->EE->session->userdata('accept_messages') != 'y')
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
		if (($this->EE->session->userdata('can_send_private_messages') != 'y' && 
			$this->EE->session->userdata('group_id') != '1') OR 
			$this->EE->session->userdata('accept_messages') != 'y')
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
	public function retrieve_password()
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

		$MA->retrieve_password();
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

		$MA->reset_password();
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

		$this->_set_page_title($this->EE->lang->line('member_search'));
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

		$this->_set_page_title($this->EE->lang->line('member_search'));
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
		if ($this->EE->session->userdata('can_delete_self') !== 'y')
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('cannot_delete_self'));
		}
		else
		{
			$delete_form = $this->_load_element('delete_confirmation_form');

			$data['hidden_fields']['ACT'] = $this->EE->functions->fetch_action_id('Member', 'member_delete');
			$data['onsubmit'] = "if( ! confirm('{lang:final_delete_confirm}')) return false;";
			$data['id']	  = 'member_delete_form';

			$this->_set_page_title($this->EE->lang->line('member_delete'));

			return $this->_var_swap($delete_form, array('form_declaration' => $this->EE->functions->form_declaration($data)));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Member self-delete
	 */
	public function member_delete()
	{
		// Make sure they got here via a form
		if ( ! $this->EE->input->post('ACT'))
		{
			// No output for you, Mr. URL Hax0r
			return FALSE;
		}

		$this->EE->lang->loadfile('login');

		// No sneakiness - we'll do this in case the site administrator
		// has foolishly turned off secure forms and some monkey is
		// trying to delete their account from an off-site form or
		// after logging out.

		if ($this->EE->session->userdata('member_id') == 0 OR 
			$this->EE->session->userdata('can_delete_self') !== 'y')
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('not_authorized'));
		}

		// If the user is a SuperAdmin, then no deletion
		if ($this->EE->session->userdata('group_id') == 1)
		{
			return $this->EE->output->show_user_error('general', $this->EE->lang->line('cannot_delete_super_admin'));
		}

		// Is IP and User Agent required for login?  Then, same here.
		if ($this->EE->config->item('require_ip_for_login') == 'y')
		{
			if ($this->EE->session->userdata('ip_address') == '' OR 
				$this->EE->session->userdata('user_agent') == '')
			{
				return $this->EE->output->show_user_error('general', $this->EE->lang->line('unauthorized_request'));
				}
		}

		// Check password lockout status
		if ($this->EE->session->check_password_lockout($this->EE->session->userdata('username')) === TRUE)
		{
			$this->EE->lang->loadfile('login');
			
			return $this->EE->output->show_user_error(
				'general', 
				sprintf(lang('password_lockout_in_effect'), $this->EE->config->item('password_lockout_interval'))
			);
		}

		// Are you who you say you are, or someone sitting at someone
		// else's computer being mean?!
		$query = $this->EE->db->select('password')
							  ->where('member_id', $this->EE->session->userdata('member_id'))
							  ->get('members');

		$password = $this->EE->functions->hash(stripslashes($this->EE->input->post('password')));

		if ($query->row('password') != $password)
		{
			$this->EE->session->save_password_lockout($this->EE->session->userdata('username'));

			return $this->EE->output->show_user_error('general', $this->EE->lang->line('invalid_pw'));
		}

		// No turning back, get to deletin'!
		$id = $this->EE->session->userdata('member_id');

		$this->EE->db->where('member_id', (int) $id)->delete('members');
		$this->EE->db->where('member_id', (int) $id)->delete('member_data');
		$this->EE->db->where('member_id', (int) $id)->delete('member_homepage');
		$this->EE->db->where('sender_id', (int) $id)->delete('message_copies');
		$this->EE->db->where('sender_id', (int) $id)->delete('message_data');
		$this->EE->db->where('sender_id', (int) $id)->delete('message_folders');
		$this->EE->db->where('sender_id', (int) $id)->delete('message_listed');

		$message_query = $this->EE->db->query("SELECT DISTINCT recipient_id FROM exp_message_copies WHERE sender_id = '{$id}' AND message_read = 'n'");

		if ($message_query->num_rows() > 0)
		{
			foreach($message_query->result_array() as $row)
			{
				$count_query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_message_copies WHERE recipient_id = '".$row['recipient_id']."' AND message_read = 'n'");
				$this->EE->db->query($this->EE->db->update_string('exp_members', array('private_messages' => $count_query->row('count') ), "member_id = '".$row['recipient_id']."'"));
			}
		}

		// Delete Forum Posts
		if ($this->EE->config->item('forum_is_installed') == "y")
		{
			$this->EE->db->where('member_id', (int) $id)->delete('forum_subscriptions');
			$this->EE->db->where('member_id', (int) $id)->delete('forum_pollvotes');
			$this->EE->db->where('author_id', (int) $id)->delete('forum_topics');
			$this->EE->db->where('admin_member_id', (int) $id)->delete('forum_administrators');
			$this->EE->db->where('mod_member_id', (int) $id)->delete('forum_moderators');

			// Snag the affected topic id's before deleting the member for the update afterwards
			$query = $this->EE->db->query("SELECT topic_id FROM exp_forum_posts WHERE author_id = '{$id}'");

			if ($query->num_rows() > 0)
			{
				$topic_ids = array();

				foreach ($query->result_array() as $row)
				{
					$topic_ids[] = $row['topic_id'];
				}

				$topic_ids = array_unique($topic_ids);
			}

			$this->EE->db->where('author_id', (int) $id)->delete('forum_posts');
			$this->EE->db->where('author_id', (int) $id)->delete('forum_polls');

			// Kill any attachments
			$query = $this->EE->db->query("SELECT attachment_id, filehash, extension, board_id FROM exp_forum_attachments WHERE member_id = '{$id}'");

			if ($query->num_rows() > 0)
			{
				// Grab the upload path
				$res = $this->EE->db->query('SELECT board_id, board_upload_path FROM exp_forum_boards');

				$paths = array();
				foreach ($res->result_array() as $row)
				{
					$paths[$row['board_id']] = $row['board_upload_path'];
				}

				foreach ($query->result_array() as $row)
				{
					if ( ! isset($paths[$row['board_id']]))
					{
						continue;
					}

					$file  = $paths[$row['board_id']].$row['filehash'].$row['extension'];
					$thumb = $paths[$row['board_id']].$row['filehash'].'_t'.$row['extension'];

					@unlink($file);
					@unlink($thumb);

					$this->EE->db->where('attachment_id', (int) $row['attachment_id'])
								 ->delete('forum_attachments');
				}
			}

			// Update the forum stats
			$query = $this->EE->db->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n'");

			if ( ! class_exists('Forum'))
			{
				require PATH_MOD.'forum/mod.forum.php';
				require PATH_MOD.'forum/mod.forum_core.php';
			}

			$FRM = new Forum_Core;

			foreach ($query->result_array() as $row)
			{
				$FRM->_update_post_stats($row['forum_id']);
			}

			if (isset($topic_ids))
			{
				foreach ($topic_ids as $topic_id)
				{
					$FRM->_update_topic_stats($topic_id);
				}
			}
		}

		// Va-poo-rize Channel Entries and Comments
		$entry_ids			= array();
		$channel_ids			= array();
		$recount_ids		= array();

		// Find Entry IDs and Channel IDs, then delete
		$query = $this->EE->db->query("SELECT entry_id, channel_id FROM exp_channel_titles WHERE author_id = '{$id}'");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$entry_ids[]	= $row['entry_id'];
				$channel_ids[]	= $row['channel_id'];
			}

			$this->EE->db->query("DELETE FROM exp_channel_titles WHERE author_id = '{$id}'");
			$this->EE->db->query("DELETE FROM exp_channel_data WHERE entry_id IN ('".implode("','", $entry_ids)."')");
			$this->EE->db->query("DELETE FROM exp_comments WHERE entry_id IN ('".implode("','", $entry_ids)."')");
		}

		// Find the affected entries AND channel ids for author's comments
		$query = $this->EE->db->query("SELECT DISTINCT(entry_id), channel_id FROM exp_comments WHERE author_id = '{$id}'");

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$recount_ids[] = $row['entry_id'];
				$channel_ids[]  = $row['channel_id'];
			}

			$recount_ids = array_diff($recount_ids, $entry_ids);
		}

		// Delete comments by member
		$this->EE->db->query("DELETE FROM exp_comments WHERE author_id = '{$id}'");

		// Update stats on channel entries that were NOT deleted AND had comments by author

		if (count($recount_ids) > 0)
		{
			foreach (array_unique($recount_ids) as $entry_id)
			{
				$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->EE->db->escape_str($entry_id)."'");

				$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

				$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '{$entry_id}' AND status = 'o'");

				$this->EE->db->query("UPDATE exp_channel_titles SET comment_total = '".$this->EE->db->escape_str($query->row('count') )."', recent_comment_date = '$comment_date' WHERE entry_id = '{$entry_id}'");
			}
		}

		if (count($channel_ids) > 0)
		{
			foreach (array_unique($channel_ids) as $channel_id)
			{
				$this->EE->stats->update_channel_stats($channel_id);
				$this->EE->stats->update_comment_stats($channel_id);
			}
		}
		
		// Email notification recipients
		if ($this->EE->session->userdata('mbr_delete_notify_emails') != '')
		{
			
			$notify_address = $this->EE->session->userdata('mbr_delete_notify_emails');

			$swap = array(
							'name'				=> $this->EE->session->userdata('screen_name'),
							'email'				=> $this->EE->session->userdata('email'),
							'site_name'			=> stripslashes($this->EE->config->item('site_name'))
						 );

			$email_tit = $this->EE->functions->var_swap($this->EE->lang->line('mbr_delete_notify_title'), $swap);
			$email_msg = $this->EE->functions->var_swap($this->EE->lang->line('mbr_delete_notify_message'), $swap);

			// No notification for the user themselves, if they're in the list
			if (strpos($notify_address, $this->EE->session->userdata('email')) !== FALSE)
			{
				$notify_address = str_replace($this->EE->session->userdata('email'), "", $notify_address);
			}

			$this->EE->load->helper('string');
			// Remove multiple commas
			$notify_address = reduce_multiples($notify_address, ',', TRUE);

			if ($notify_address != '')
			{
				// Send email
				$this->EE->load->library('email');

				// Load the text helper
				$this->EE->load->helper('text');

				foreach (explode(',', $notify_address) as $addy)
				{
					$this->EE->email->EE_initialize();
					$this->EE->email->wordwrap = FALSE;
					$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
					$this->EE->email->to($addy);
					$this->EE->email->reply_to($this->EE->config->item('webmaster_email'));
					$this->EE->email->subject($email_tit);
					$this->EE->email->message(entities_to_ascii($email_msg));
					$this->EE->email->send();
				}
			}
		}

		// Trash the Session and cookies
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'))
					 ->where('ip_address', $this->EE->input->ip_address())
					 ->where('member_id', (int) $id)
					 ->delete('online_users');

		$this->EE->db->where('session_id', $this->EE->session->userdata('session_id'))
					 ->delete('sessions');

		$this->EE->functions->set_cookie($this->EE->session->c_session);
		$this->EE->functions->set_cookie($this->EE->session->c_expire);
		$this->EE->functions->set_cookie($this->EE->session->c_anon);
		$this->EE->functions->set_cookie('read_topics');
		$this->EE->functions->set_cookie('tracker');

		// Update
		$this->EE->stats->update_member_stats();

		// Build Success Message
		$url	= $this->EE->config->item('site_url');
		$name	= stripslashes($this->EE->config->item('site_name'));

		$data = array(	'title' 	=> $this->EE->lang->line('mbr_delete'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('mbr_account_deleted'),
						'redirect'	=> '',
						'link'		=> array($url, $name)
					 );

		$this->EE->output->show_message($data);
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
		if ($this->EE->config->item('user_session_type') != 'c')
		{
			$this->EE->TMPL->tagdata = preg_replace("/{if\s+auto_login}.*?{".'\/'."if}/s", '', $this->EE->TMPL->tagdata);
		}
		else
		{
			$this->EE->TMPL->tagdata = preg_replace("/{if\s+auto_login}(.*?){".'\/'."if}/s", "\\1", $this->EE->TMPL->tagdata);
		}

		// Create form
		$data['hidden_fields'] = array(
										'ACT' => $this->EE->functions->fetch_action_id('Member', 'member_login'),
										'RET' => ($this->EE->TMPL->fetch_param('return') && $this->EE->TMPL->fetch_param('return') != "") ? $this->EE->TMPL->fetch_param('return') : '-2'
									  );

		if ($this->EE->TMPL->fetch_param('name') !== FALSE &&
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $this->EE->TMPL->fetch_param('name'), $match))
		{
			$data['name'] = $this->EE->TMPL->fetch_param('name');
			$this->EE->TMPL->log_item('Member Login Form:  The \'name\' parameter has been deprecated.  Please use form_name');
		}
		elseif ($this->EE->TMPL->fetch_param('form_name') && $this->EE->TMPL->fetch_param('form_name') != "")
		{
			$data['name'] = $this->EE->TMPL->fetch_param('form_name');
		}

		if ($this->EE->TMPL->fetch_param('id') !== FALSE && 
			preg_match("#^[a-zA-Z0-9_\-]+$#i", $this->EE->TMPL->fetch_param('id')))
		{
			$data['id'] = $this->EE->TMPL->fetch_param('id');
			$this->EE->TMPL->log_item('Member Login Form:  The \'id\' parameter has been deprecated.  Please use form_id');
		}
		else
		{
			$data['id'] = $this->EE->TMPL->form_id;
		}
		
		$data['class'] = $this->EE->TMPL->form_class;


		$res  = $this->EE->functions->form_declaration($data);

		$res .= stripslashes($this->EE->TMPL->tagdata);

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
		if ($this->EE->session->userdata('member_id') == 0)
		{
			return $this->EE->output->fatal_error($this->EE->lang->line('must_be_logged_in'));
		}

		$class_path = PATH_MOD.'emoticon/emoticons.php';

		if ( ! is_file($class_path) OR ! @include_once($class_path))
		{
			return $this->EE->output->fatal_error('Unable to locate the smiley images');
		}

		if ( ! is_array($smileys))
		{
			return;
		}

		$path = $this->EE->config->slash_item('emoticon_url');

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

		$this->_set_page_title($this->EE->lang->line('smileys'));
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

		return $this->_var_swap($this->EE->TMPL->tagdata,
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
			$theme = ($this->EE->config->item('member_theme') == '') ? 'default' : $this->EE->config->item('member_theme');
			$this->theme_path = PATH_MBR_THEMES."{$theme}/";			
		}

		if ( ! file_exists($this->theme_path.$which.'.html'))
		{
			$data = array(	'title' 	=> $this->EE->lang->line('error'),
							'heading'	=> $this->EE->lang->line('general_error'),
							'content'	=> $this->EE->lang->line('nonexistent_page'),
							'redirect'	=> '',
							'link'		=> array($this->EE->config->item('site_url'), stripslashes($this->EE->config->item('site_name')))
						 );

			return $this->EE->output->show_message($data, 0);			
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
										'lang:heading'	=>	$this->EE->lang->line($heading),
										'lang:message'	=>	($use_lang == TRUE) ? $this->EE->lang->line($message) : $message
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
												'link'	=> $this->EE->config->item('site_url'),
												'title'	=> stripslashes($this->EE->config->item('site_name'))
											 )
									);

			if ($this->EE->uri->segment(2) == '')
			{
				return $this->_build_crumbs($this->EE->lang->line('member_profile'), $crumbs, $this->EE->lang->line('member_profile'));
			}

			if ($this->EE->uri->segment(2) == 'messages')
			{
				$crumbs .= $this->_crumb_trail(array(
													'link' => $this->_member_path('/profile'),
													'title' => $this->EE->lang->line('control_panel_home')
													)
												);

				$pm_page =  (FALSE !== ($mbr_crumb = $this->_fetch_member_crumb($this->EE->uri->segment(3)))) ? $this->EE->lang->line($mbr_crumb) : $this->EE->lang->line('view_folder');

				return $this->_build_crumbs($pm_page, $crumbs, $pm_page);
			}


			if (is_numeric($this->EE->uri->segment(2)))
			{
				$query = $this->EE->db->query("SELECT screen_name FROM exp_members WHERE member_id = '".$this->EE->uri->segment(2)."'");

				$crumbs .= $this->_crumb_trail(array(
													'link' => $this->_member_path('/memberlist'),
													'title' => $this->EE->lang->line('mbr_memberlist')
													)
												);

				return $this->_build_crumbs($query->row('screen_name') , $crumbs, $query->row('screen_name') );
			}
			else
			{
				if ($this->EE->uri->segment(2) == 'memberlist')
				{
					return $this->_build_crumbs($this->EE->lang->line('mbr_memberlist'), $crumbs, $this->EE->lang->line('mbr_memberlist'));
				}
				elseif ($this->EE->uri->segment(2) == 'member_search' OR $this->EE->uri->segment(2) == 'do_member_search')
				{
					return $this->_build_crumbs($this->EE->lang->line('member_search'), $crumbs, $this->EE->lang->line('member_search'));
				}
				elseif ($this->EE->uri->segment(2) != 'profile' AND ! in_array($this->EE->uri->segment(2), $this->no_menu))
				{
					$crumbs .= $this->_crumb_trail(array(
														'link' => $this->_member_path('/profile'),
														'title' => $this->EE->lang->line('control_panel_home')
														)
													);
				}

			}

			if (FALSE !== ($mbr_crumb = $this->_fetch_member_crumb($this->EE->uri->segment(2))))
			{
				return $this->_build_crumbs($this->EE->lang->line($mbr_crumb), $crumbs, $this->EE->lang->line($mbr_crumb));
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

		$breadcrumb = str_replace('{name}', $this->EE->session->userdata('screen_name'), $breadcrumb);

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

		$r .= "<option value=''{$selected}>".$this->EE->lang->line('year')."</option>\n";

		for ($i = date('Y', $this->EE->localize->now); $i > 1904; $i--)
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

		$r .= "<option value=''{$selected}>".$this->EE->lang->line('month')."</option>\n";

		for ($i = 1; $i < 13; $i++)
		{
			if (strlen($i) == 1)
				$i = '0'.$i;

			$selected = ($month == $i) ? " selected='selected'" : '';

			$r .= "<option value='{$i}'{$selected}>".$this->EE->lang->line($months[$i])."</option>\n";
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

		$r .= "<option value=''{$selected}>".$this->EE->lang->line('day')."</option>\n";

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

		if ($this->EE->session->userdata('member_id') == 0)
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
		if ($this->EE->config->item('forum_is_installed') == "y")
		{
			$str = $this->_allow_if('forum_installed', $str);
		}
		else
		{
			$str = $this->_deny_if('forum_installed', $str);
		}

		// Parse the self deletion conditional
		if ($this->EE->session->userdata('can_delete_self') == 'y' && 
			$this->EE->session->userdata('group_id') != 1)
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
			if ($this->EE->TMPL->tagdata != '')
			{
				$str = $this->_parse_index_template($str);
				$template_parser = TRUE;
				$this->EE->TMPL->disable_caching = TRUE;
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
				$line = ($this->EE->lang->line($matches['1'][$j]) == $matches['1'][$j]) ? $this->EE->lang->line('mbr_'.$matches['1'][$j]) : $this->EE->lang->line($matches['1'][$j]);

				$str = str_replace($matches['0'][$j], $line, $str);
			}
		}

		// Parse old style path variables
		// This is here for backward compatibility for people with older templates
		$str = preg_replace_callback("/".LD."\s*path=(.*?)".RD."/", array(&$this->EE->functions, 'create_url'), $str);

		if (preg_match_all("#".LD."\s*(profile_path\s*=.*?)".RD."#", $str, $matches))
		{
			$i = 0;
			foreach ($matches['1'] as $val)
			{
				$path = $this->EE->functions->create_url($this->EE->functions->extract_path($val).'/'.$this->EE->session->userdata('member_id'));
				$str = preg_replace("#".$matches['0'][$i++]."#", $path, $str, 1);
			}
		}
		// -------

		// Set some paths
		$theme_images = $this->EE->config->slash_item('theme_folder_url', 1).'profile_themes/'.$this->EE->config->item('member_theme').'/images/';

		if ($this->EE->session->userdata('profile_theme') != '')
		{
			$img_path = $this->EE->config->slash_item('theme_folder_url').'profile_themes/'.$this->EE->session->userdata('profile_theme').'/images/';
		}
		else
		{
			$img_path = $this->EE->config->slash_item('theme_folder_url', 1).'profile_themes/'.$this->EE->config->item('member_theme').'/images/';
		}

		$simple = ($this->show_headings == FALSE) ? '/simple' : '';

		if ($this->css_file_path == '')
		{
			$this->css_file_path = $this->EE->config->slash_item('theme_folder_url', 1).'profile_themes/'.$this->EE->config->item('member_theme').'profile.css';
		}
		
		// Parse {switch="foo|bar"} variables
		if (preg_match_all("/".LD."(switch\s*=.+?)".RD."/i", $str, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$sparam = $this->EE->functions->assign_parameters($match[1]);

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
		$str = $this->EE->functions->prep_conditionals($str, array('current_request' => $this->request));
		
		$str = $this->_var_swap($str,
								array(
										'lang'						=> $this->EE->config->item('xml_lang'),
										'charset'					=> $this->EE->config->item('output_charset'),
										'path:image_url'			=> ($this->image_url == '') ? $theme_images : $this->image_url,
										'path:your_control_panel'	=> $this->_member_path('profile'),
										'path:your_profile'			=> $this->_member_path($this->EE->session->userdata('member_id')),
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
										'site_name'					=> stripslashes($this->EE->config->item('site_name')),
										'path:theme_css'			=> $this->css_file_path,
										'current_request'			=> $this->request
									)
								 );

		// parse regular global vars
		$this->EE->load->library('template', NULL, 'TMPL');
		
		// load up any Snippets
		$this->EE->db->select('snippet_name, snippet_contents');
		$this->EE->db->where('(site_id = '.$this->EE->db->escape_str($this->EE->config->item('site_id')).' OR site_id = 0)');
		$fresh = $this->EE->db->get('snippets');
		
		if ($fresh->num_rows() > 0)
		{
			$snippets = array();
			
			foreach ($fresh->result() as $var)
			{
				$snippets[$var->snippet_name] = $var->snippet_contents;
			}
			
			$this->EE->config->_global_vars = array_merge($this->EE->config->_global_vars, $snippets);
			
			unset($snippets);
			unset($fresh);
		}
		
		$this->EE->TMPL->parse($str);
		$str = $this->EE->TMPL->parse_globals($this->EE->TMPL->final_template);
		
		//  Add security hashes to forms
		if ( ! class_exists('Template'))
		{
			$str = $this->EE->functions->insert_action_ids($this->EE->functions->add_form_security_hash($str));
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
		$this->basepath = $this->EE->functions->create_url($this->trigger);
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

		return $this->EE->functions->remove_double_slashes($this->basepath.'/'.$uri);
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
	function _var_swap_single($search, $replace, $source)
	{
		return str_replace(LD.$search.RD, $replace, $source);
	}

	// --------------------------------------------------------------------

	/**
	 * Custom Member Profile Data
	 */
	function custom_profile_data()
	{

		$member_id = ( ! $this->EE->TMPL->fetch_param('member_id')) ? $this->EE->session->userdata('member_id') : $this->EE->TMPL->fetch_param('member_id');

		// Default Member Data
		$this->EE->db->select('m.member_id, m.group_id, m.username, m.screen_name, m.email, m.signature,
							m.avatar_filename, m.avatar_width, m.avatar_height,
							m.photo_filename, m.photo_width, m.photo_height,
							m.url, m.location, m.occupation, m.interests,
							m.bio,
							m.join_date, m.last_visit, m.last_activity, m.last_entry_date, m.last_comment_date,
							m.last_forum_post_date, m.total_entries, m.total_comments, m.total_forum_topics, m.total_forum_posts,
							m.language, m.timezone, m.daylight_savings, m.bday_d, m.bday_m, m.bday_y,
							g.group_title');
		$this->EE->db->from(array('members m', 'member_groups g'));
		$this->EE->db->where('m.member_id', $member_id);
		$this->EE->db->where('g.site_id', $this->EE->config->item('site_id'));
		$this->EE->db->where('m.group_id = g.group_id');
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->tagdata = '';
		}

		$default_fields = $query->row_array();
		
		// Is there an avatar?
		if ($this->EE->config->item('enable_avatars') == 'y' AND $query->row('avatar_filename') != '')
		{
			$avatar_path	= $this->EE->config->item('avatar_url').$query->row('avatar_filename');
			$avatar_width	= $query->row('avatar_width');
			$avatar_height	= $query->row('avatar_height');
			$avatar			= 'TRUE';
		}
		else
		{
			$avatar_path	= '';
			$avatar_width	= '';
			$avatar_height	= '';
			$avatar			= 'FALSE';
		}

		// Is there a member photo?
		if ($this->EE->config->item('enable_photos') == 'y' AND $query->row('photo_filename') != '')
		{
			$photo_path		= $this->EE->config->item('photo_url').$query->row('photo_filename');
			$photo_width	= $query->row('photo_width');
			$photo_height	= $query->row('photo_height');
			$photo			= 'TRUE';
		}
		else
		{
			$photo_path	= '';
			$photo_width	= '';
			$photo_height	= '';
			$photo			= 'FALSE';
		}

		// Is there a signature image?
		if ($this->EE->config->item('enable_signatures') == 'y' AND $query->row('sig_img_filename') != '')
		{
			$sig_img_path	= $this->EE->config->item('sig_img_url').$query->row('sig_img_filename');
			$sig_img_width	= $query->row('sig_img_width');
			$sig_img_height	= $query->row('sig_img_height');
			$sig_img_image	= 'TRUE';
		}
		else
		{
			$sig_img_path	= '';
			$sig_img_width	= '';
			$sig_img_height	= '';
			$sig_img		= 'FALSE';
		}

		// Parse variables
		if ($this->in_forum == TRUE)
		{
			$search_path = $this->forum_path.'member_search/'.$this->cur_id.'/';
		}
		else
		{
			$search_path = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Search', 'do_search').'&amp;mbr='.urlencode($query->row('member_id'));
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

		$this->EE->db->select('m_field_id, m_field_name, m_field_fmt');
		$query = $this->EE->db->get('member_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$fields[$row['m_field_name']] = array($row['m_field_id'], $row['m_field_fmt']);
			}
		}

		$this->EE->db->where('member_id', $member_id);
		$query = $this->EE->db->get('member_data');

		if ($query->num_rows() == 0)
		{
			foreach ($fields as $key => $val)
			{
				$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single($key, '', $this->EE->TMPL->tagdata);
			}

			return $this->EE->TMPL->tagdata;
		}

		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		$cond = $default_fields;

		foreach ($query->result_array() as $row)
		{
			$cond['avatar']	= $avatar;
			$cond['photo'] = $photo;
			
			foreach($fields as $key =>  $value)
			{
				$cond[$key] = $this->EE->typography->parse_type($row['m_field_id_'.$value['0']],
												array(
													  'text_format'	=> $value['1'],
													  'html_format'	=> 'safe',
													  'auto_links'	=> 'y',
													  'allow_img_url' => 'n'
													 )
										  	  );
			}

			$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $cond);

			// Swap Variables
			foreach ($this->EE->TMPL->var_single as $key => $val)
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
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_visit'] > 0) ? $this->EE->localize->decode_date($val, $default_fields['last_visit']) : '', $this->EE->TMPL->tagdata);
				}

				//  "last_activity"
				if (strncmp($key, 'last_activity', 10) == 0)
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_activity'] > 0) ? $this->EE->localize->decode_date($val, $default_fields['last_activity']) : '', $this->EE->TMPL->tagdata);
				}
				
				//  "join_date"
				if (strncmp($key, 'join_date', 9) == 0)
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['join_date'] > 0) ? $this->EE->localize->decode_date($val, $default_fields['join_date']) : '', $this->EE->TMPL->tagdata);
				}

				//  "last_entry_date"
				if (strncmp($key, 'last_entry_date', 15) == 0)
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_entry_date'] > 0) ? $this->EE->localize->decode_date($val, $default_fields['last_entry_date']) : '', $this->EE->TMPL->tagdata);
				}

				//  "last_forum_post_date"
				if (strncmp($key, 'last_forum_post_date', 20) == 0)
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_forum_post_date'] > 0) ? $this->EE->localize->decode_date($val, $default_fields['last_forum_post_date']) : '', $this->EE->TMPL->tagdata);
				}

				//  parse "recent_comment"
				if (strncmp($key, 'last_comment_date', 17) == 0)
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, ($default_fields['last_comment_date'] > 0) ? $this->EE->localize->decode_date($val, $default_fields['last_comment_date']) : '', $this->EE->TMPL->tagdata);
				}

				//  {name}
				$name = ( ! $default_fields['screen_name']) ? $default_fields['username'] : $default_fields['screen_name'];

				$name = $this->_convert_special_chars($name);

				if ($key == "name")
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($val, $name, $this->EE->TMPL->tagdata);
				}

				//  {member_group}
				if ($key == "member_group")
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($val, $default_fields['group_title'], $this->EE->TMPL->tagdata);
				}

				//  {email}
				if ($key == "email")
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($val, $this->EE->typography->encode_email($default_fields['email']), $this->EE->TMPL->tagdata);
				}

				//  {birthday}
				if ($key == "birthday")
				{
					$birthday = '';

					if ($default_fields['bday_m'] != '' AND $default_fields['bday_m'] != 0)
					{
						$month = (strlen($default_fields['bday_m']) == 1) ? '0'.$default_fields['bday_m'] : $default_fields['bday_m'];

						$m = $this->EE->localize->localize_month($month);

						$birthday .= $this->EE->lang->line($m['1']);

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

					$this->EE->TMPL->tagdata = $this->_var_swap_single($val, $birthday, $this->EE->TMPL->tagdata);
				}

				//  {timezone}
				if ($key == "timezone")
				{
					$timezone = ($default_fields['timezone'] != '') ? $this->EE->lang->line($default_fields['timezone']) : '';

					$this->EE->TMPL->tagdata = $this->_var_swap_single($val, $timezone, $this->EE->TMPL->tagdata);
				}

				//  {local_time}
				if (strncmp($key, 'local_time', 10) == 0)
				{
					$time = $this->EE->localize->now;

					if ($this->EE->session->userdata('member_id') != $this->cur_id)
					{  
						// Default is UTC?
						$zone = ($default_fields['timezone'] == '') ? 'UTC' : $default_fields['timezone'];
						$time = $this->EE->localize->set_localized_time($time, $zone, $default_fields['daylight_savings']);
					}

					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, $this->EE->localize->decode_date($val, $time), $this->EE->TMPL->tagdata);
				}

				//  {bio}
				if ($key == 'bio')
				{
					$bio = $this->EE->typography->parse_type($default_fields[$val],
																 array(
																			'text_format'   => 'xhtml',
																			'html_format'   => 'safe',
																			'auto_links'    => 'y',
																			'allow_img_url' => 'n'
																	   )
																);

					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, $bio, $this->EE->TMPL->tagdata);
				}

				// Special condideration for {total_forum_replies}, and
				// {total_forum_posts} whose meanings do not match the
				// database field names
				if ($key == 'total_forum_replies')
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, $default_fields['total_forum_posts'], $this->EE->TMPL->tagdata);
				}

				if ($key == 'total_forum_posts')
				{
					$total_posts = $default_fields['total_forum_topics'] + $default_fields['total_forum_posts'];
					$this->EE->TMPL->tagdata = $this->_var_swap_single($key, $total_posts, $this->EE->TMPL->tagdata);
				}

				// parse basic fields (username, screen_name, etc.)
				if (array_key_exists($key, $default_fields))
				{
					$this->EE->TMPL->tagdata = $this->_var_swap_single($val, $default_fields[$val], $this->EE->TMPL->tagdata);
				}

				// parse custom member fields
				if (isset($fields[$val]) && array_key_exists('m_field_id_'.$fields[$val]['0'], $row))
				{
					$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single(
														$val,
														$this->EE->typography->parse_type(
															$row['m_field_id_'.$fields[$val]['0']],
																				array(
																						'text_format'	=> $fields[$val]['1'],
																						'html_format'	=> 'safe',
																						'auto_links'	=> 'y',
																						'allow_img_url' => 'n'
																					  )
																			  ),
														$this->EE->TMPL->tagdata
													  );
				}
				//else { echo 'm_field_id_'.$fields[$val]['0']; }
			}
		}

		return $this->EE->TMPL->tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Ignore List
	 */
	function ignore_list()
	{
		$pre = 'ignore_';
		$prelen = strlen($pre);

		if ($member_id = $this->EE->TMPL->fetch_param('member_id'))
		{
			$query = $this->EE->db->query("SELECT ignore_list FROM exp_members WHERE member_id = '{$member_id}'");

			if ($query->num_rows() == 0)
			{
				return $this->EE->TMPL->no_results();
			}

			$ignored = ($query->row('ignore_list')  == '') ? array() : explode('|', $query->row('ignore_list') );
		}
		else
		{
			$ignored = $this->EE->session->userdata('ignore_list');
		}

		$query = $this->EE->db->query("SELECT m.member_id, m.group_id, m.username, m.screen_name, m.email, m.ip_address, m.location, m.total_entries, m.total_comments, m.private_messages, m.total_forum_topics, m.total_forum_posts AS total_forum_replies, m.total_forum_topics + m.total_forum_posts AS total_forum_posts,
							g.group_title AS group_description FROM exp_members AS m, exp_member_groups AS g
							WHERE g.group_id = m.group_id
							g.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'
							AND m.member_id IN ('".implode("', '", $ignored)."')");

		if ($query->num_rows() == 0)
		{
			return $this->EE->TMPL->no_results();
		}

		$tagdata = $this->EE->TMPL->tagdata;
		$out = '';

		foreach($query->result_array() as $row)
		{
			$temp = $tagdata;

			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				$val = substr($val, $prelen);

				if (isset($row[$val]))
				{
					$temp = $this->EE->TMPL->swap_var_single($pre.$val, $row[$val], $temp);
				}
			}

			$out .= $temp;
		}

		return $this->EE->TMPL->tagdata = $out;
	}
}
// END CLASS

/* End of file mod.member.php */
/* Location: ./system/expressionengine/modules/member/mod.member.php */