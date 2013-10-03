<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Cp {

	private $EE;
	private $view;

	var $cp_theme				= '';
	var $cp_theme_url			= '';	// base URL to the CP theme folder

	var $installed_modules		= FALSE;

	var $its_all_in_your_head	= array();
	var $footer_item			= array();
	var $requests				= array();
	var $loaded					= array();

	var $js_files = array(
			'ui'				=> array(),
			'plugin'			=> array(),
			'file'				=> array(),
			'package'			=> array(),
			'fp_module'			=> array()
	);


	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		if (ee()->router->fetch_class() == 'ee')
		{
			show_error("The CP library is only available on Control Panel requests.");
		}

		// Cannot set these in the installer
		if ( ! defined('EE_APPPATH'))
		{
			$this->cp_theme	= ( ! ee()->session->userdata('cp_theme')) ? ee()->config->item('cp_theme') : ee()->session->userdata('cp_theme');
			$this->cp_theme_url = ee()->config->slash_item('theme_folder_url').'cp_themes/'.$this->cp_theme.'/';

			ee()->load->vars(array(
				'cp_theme_url'	=> $this->cp_theme_url
			));
		}

		// Make sure all requests to iframe the CP are denied
		ee()->output->set_header('X-Frame-Options: SameOrigin');
	}


	// --------------------------------------------------------------------

	/**
	 * Set Certain Default Control Panel View Variables
	 *
	 * @access	public
	 * @return	void
	 */
	function set_default_view_variables()
	{
		$js_folder	= (ee()->config->item('use_compressed_js') == 'n') ? 'src' : 'compressed';
		$langfile	= substr(ee()->router->class, 0, strcspn(ee()->router->class, '_'));

		// Javascript Path Constants

		define('PATH_JQUERY', PATH_THEMES.'javascript/'.$js_folder.'/jquery/');
		define('PATH_JAVASCRIPT', PATH_THEMES.'javascript/'.$js_folder.'/');
		define('JS_FOLDER', $js_folder);


		ee()->load->library('javascript', array('autoload' => FALSE));

		ee()->load->model('member_model'); // for screen_name, quicklinks

		ee()->lang->loadfile($langfile);


		// Success/failure messages

		$cp_messages = array();

		foreach (array('message_success', 'message_notice', 'message_error', 'message_failure') as $flash_key)
		{
			if ($message = ee()->session->flashdata($flash_key))
			{
				$flash_key = ($flash_key == 'message_failure') ? 'error' : substr($flash_key, 8);
				$cp_messages[$flash_key] = $message;
			}
		}

		$cp_table_template = array(
			'table_open' => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">'
		);

		$cp_pad_table_template = array(
			'table_open' => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">'
		);

		$user_q = ee()->member_model->get_member_data(
			ee()->session->userdata('member_id'),
			array(
				'screen_name', 'notepad', 'quick_links',
				'avatar_filename', 'avatar_width', 'avatar_height'
			)
		);

		$notepad_content = ($user_q->row('notepad')) ? $user_q->row('notepad') : '';

		// Global view variables

		$vars =	array(
			'cp_page_onload'		=> '',
			'cp_page_title'			=> '',
			'cp_breadcrumbs'		=> array(),
			'cp_right_nav'			=> array(),
			'cp_messages'			=> $cp_messages,
			'cp_notepad_content'	=> $notepad_content,
			'cp_table_template'		=> $cp_table_template,
			'cp_pad_table_template'	=> $cp_pad_table_template,
			'cp_theme_url'			=> $this->cp_theme_url,
			'cp_current_site_label'	=> ee()->config->item('site_name'),
			'cp_screen_name'		=> $user_q->row('screen_name'),
			'cp_avatar_path'		=> $user_q->row('avatar_filename') ? ee()->config->slash_item('avatar_url').$user_q->row('avatar_filename') : '',
			'cp_avatar_width'		=> $user_q->row('avatar_filename') ? $user_q->row('avatar_width') : '',
			'cp_avatar_height'		=> $user_q->row('avatar_filename') ? $user_q->row('avatar_height') : '',
			'cp_quicklinks'			=> $this->_get_quicklinks($user_q->row('quick_links')),

			'EE_view_disable'		=> FALSE,
			'is_super_admin'		=> (ee()->session->userdata['group_id'] == 1) ? TRUE : FALSE,	// for conditional use in view files
		);


		// global table data
		ee()->session->set_cache('table', 'cp_template', $cp_table_template);
		ee()->session->set_cache('table', 'cp_pad_template', $cp_pad_table_template);

		// we need these paths again in my account, so we'll keep track of them
		// kind of hacky, but before it was accessing _ci_cache_vars, which is worse

		ee()->session->set_cache('cp_sidebar', 'cp_avatar_path', $vars['cp_avatar_path'])
						  ->set_cache('cp_sidebar', 'cp_avatar_width', $vars['cp_avatar_width'])
						  ->set_cache('cp_sidebar', 'cp_avatar_height', $vars['cp_avatar_height']);

		if (ee()->router->method != 'index')
		{
			$this->set_breadcrumb(BASE.AMP.'C='.ee()->router->class, lang(ee()->router->class));
		}

		// The base javascript variables that will be available globally through EE.varname
		// this really could be made easier - ideally it would show up right below the main
		// jQuery script tag - before the plugins, so that it has access to jQuery.

		// If you use it in your js, please uniquely identify your variables - or create
		// another object literal:
		// Bad: EE.test = "foo";
		// Good: EE.unique_foo = "bar"; EE.unique = { foo : "bar"};

		$js_lang_keys = array(
			'logout'			=> lang('logout'),
			'search'			=> lang('search'),
			'session_idle'		=> lang('session_idle')
		);

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- login_reminder => y/n  to turn the CP Login Reminder On or Off.  Default is 'y'
        /* -------------------------------------------*/

		if (ee()->config->item('login_reminder') != 'n')
		{
			$js_lang_keys['session_expiring'] = lang('session_expiring');
			$js_lang_keys['username'] = lang('username');
			$js_lang_keys['password'] = lang('password');
			$js_lang_keys['login'] = lang('login');

			ee()->javascript->set_global(array(
				'SESS_TIMEOUT'		=> ee()->session->cpan_session_len * 1000,
				'SESS_TYPE'			=> ee()->config->item('admin_session_type')
			));
		}

		ee()->javascript->set_global(array(
			'BASE'				=> str_replace(AMP, '&', BASE),
			'XID'				=> XID_SECURE_HASH,
			'PATH_CP_GBL_IMG'	=> PATH_CP_GBL_IMG,
			'CP_SIDEBAR_STATE'	=> ee()->session->userdata('show_sidebar'),
			'username'			=> ee()->session->userdata('username'),
			'router_class'		=> ee()->router->class, // advanced css
			'lang'				=> $js_lang_keys,
			'THEME_URL'			=> $this->cp_theme_url
		));

		// Combo-load the javascript files we need for every request

		$js_scripts = array(
			'ui'		=> array('core', 'widget', 'mouse', 'position', 'sortable', 'dialog'),
			'plugin'	=> array('ee_interact.event', 'ee_broadcast.event', 'ee_notice', 'ee_txtarea', 'tablesorter', 'ee_toggle_all'),
			'file'		=> array('json2', 'underscore', 'cp/global_start')
		);

		if ($this->cp_theme != 'mobile')
		{
			$js_scripts['plugin'][] = 'ee_navigation';
		}

		$this->add_js_script($js_scripts);
		$this->_seal_combo_loader();

		ee()->load->vars($vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Render output (html)
	 *
	 * @access public
	 * @return void
	 */
	public function render($view, $data = array(), $return = FALSE)
	{
		$this->_menu();
		$this->_accessories();
		$this->_sidebar();

		if (isset(ee()->table))
		{
			// We have a code order issue with accessories.
			// If an accessory changed the table template (this happens
			// a lot due to differences in design), we need to re-set the CP
			// template. Otherwise this is set in the table lib constructor.
			ee()->table->set_template(
				ee()->session->cache('table', 'cp_template')
			);
		}

		// add global end file
		$this->_seal_combo_loader();
		$this->add_js_script('file', 'cp/global_end');

		return ee()->view->render($view, $data, $return);
	}

	// --------------------------------------------------------------------

	/**
	 * Load up accessories for our view
	 *
	 * @access public
	 * @return void
	 */
	protected function _accessories()
	{
		if (ee()->view->disabled('ee_accessories'))
		{
			return;
		}

		ee()->load->library('accessories');
		ee()->view->cp_accessories = ee()->accessories->generate_accessories();
	}

	// --------------------------------------------------------------------

	/**
	 * Load up the menu for our view
	 *
	 * @access public
	 * @return void
	 */
	protected function _menu()
	{
		if (ee()->view->disabled('ee_menu'))
		{
			return;
		}

		ee()->load->library('menu');
		ee()->view->cp_menu_items = ee()->menu->generate_menu();
	}

	// --------------------------------------------------------------------

	/**
	 * Load up the sidebar for our view
	 *
	 * @access public
	 * @return void
	 */
	protected function _sidebar()
	{
		ee()->view->sidebar_state = '';
		ee()->view->maincontent_state = '';

		if (ee()->session->userdata('show_sidebar') == 'n')
		{
			ee()->view->sidebar_state = ' style="display:none"';
			ee()->view->maincontent_state = ' style="width:100%; display:block"';
        }

        if (ee()->view->disabled('ee_sidebar'))
		{
			return;
		}

		// @todo move over sidebar content from set_default_view_vars
		// has a member query & session cache dependency
	}

	// --------------------------------------------------------------------

	/**
	 * Mask URL.
	 *
	 * To be used to create url's that "mask" the real location of the
	 * users control panel.  Eg:  http://example.com/index.php?URL=http://example2.com
	 *
	 * @access public
	 * @param string	URL
	 * @return string	Masked URL
	 */
	function masked_url($url)
	{
		return ee()->functions->fetch_site_index(0,0).QUERY_MARKER.'URL='.urlencode($url);
	}

	// --------------------------------------------------------------------

	/**
	 * Add JS Script
	 *
	 * Adds a javascript file to the javascript combo loader
	 *
	 * @access public
	 * @param array - associative array of
	 */
	function add_js_script($script = array(), $in_footer = TRUE)
	{
		if ( ! is_array($script))
		{
			if (is_bool($in_footer))
			{
				return FALSE;
			}

			$script = array($script => $in_footer);
			$in_footer = TRUE;
		}

		if ( ! $in_footer)
		{
			return $this->its_all_in_your_head = array_merge($this->its_all_in_your_head, $script);
		}

		foreach ($script as $type => $file)
		{
			if ( ! is_array($file))
			{
				$file = array($file);
			}

			if (array_key_exists($type, $this->js_files))
			{
				$this->js_files[$type] = array_merge($this->js_files[$type], $file);
			}
			else
			{
				$this->js_files[$type] = $file;
			}
		}

		return $this->js_files;
	}

	// --------------------------------------------------------------------

	/**
	 * Render Footer Javascript
	 *
	 * @access public
	 * @return string
	 */
	function render_footer_js()
	{
		$str = '';
		$requests = $this->_seal_combo_loader();

		foreach($requests as $req)
		{
			$str .= '<script type="text/javascript" charset="utf-8" src="'.BASE.AMP.'C=javascript'.AMP.'M=combo_load'.$req.'"></script>';
		}

		if (ee()->extensions->active_hook('cp_js_end') === TRUE)
		{
			$str .= '<script type="text/javascript" src="'.BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'file=ext_scripts"></script>';
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Seal the current combo loader and reopen a new one.
	 *
	 * @access	private
	 * @return	array
	 */
	function _seal_combo_loader()
	{
		$str = '';
		$mtimes = array();

		$this->js_files = array_map('array_unique', $this->js_files);

		foreach ($this->js_files as $type => $files)
		{
			if (isset($this->loaded[$type]))
			{
				$files = array_diff($files, $this->loaded[$type]);
			}

			if (count($files))
			{
				$mtimes[] = $this->_get_js_mtime($type, $files);
				$str .= AMP.$type.'='.implode(',', $files);
			}
		}

		if ($str)
		{
			$this->loaded = array_merge_recursive($this->loaded, $this->js_files);

			$this->js_files = array(
					'ui'				=> array(),
					'plugin'			=> array(),
					'file'				=> array(),
					'package'			=> array(),
					'fp_module'			=> array()
			);

			$this->requests[] = $str.AMP.'v='.max($mtimes);
		}

		return $this->requests;
	}

	// --------------------------------------------------------------------

	/**
	 * Get last modification time of a js file.
	 * Returns highest if passed an array.
	 *
	 * @access	private
	 * @param	string
	 * @param	mixed
	 * @return	int
	 */
	function _get_js_mtime($type, $name)
	{
		if (is_array($name))
		{
			$mtimes = array();

			foreach($name as $file)
			{
				$mtimes[] = $this->_get_js_mtime($type, $file);
			}

			return max($mtimes);
		}

		$folder = ee()->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed';

		switch($type)
		{
			case 'ui':			$file = PATH_THEMES.'javascript/'.$folder.'/jquery/ui/jquery.ui.'.$name.'.js';
				break;
			case 'plugin':		$file = PATH_THEMES.'javascript/'.$folder.'/jquery/plugins/'.$name.'.js';
				break;
			case 'file':		$file = PATH_THEMES.'javascript/'.$folder.'/'.$name.'.js';
				break;
			case 'package':		$file = PATH_THIRD.$name.'/javascript/'.$name.'.js';
				break;
			case 'fp_module':	$file = PATH_MOD.$name.'/javascript/'.$name.'.js';
				break;
			default:
				return 0;
		}

		return file_exists($file) ? filemtime($file) : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the right navigation
	 *
	 * @access	public
	 * @param	array
	 * @param	string
	 * @return	int
	 */
	function set_right_nav($nav = array())
	{
		ee()->view->cp_right_nav = array_reverse($nav);
	}

	// --------------------------------------------------------------------

	/**
	 * Set the in-header navigation
	 *
	 * @access	public
	 * @param	array
	 * @param	string
	 * @return	int
	 */
	function set_action_nav($nav = array())
	{
		ee()->view->cp_action_nav = array_reverse($nav);
	}

	// --------------------------------------------------------------------

	/**
	 * Updates saved publish layouts
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function delete_layout_tabs($tabs = array(), $namespace = '', $channel_id = array())
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6', 'Layout::delete_layout_tabs()');

		ee()->load->library('layout');
		return ee()->layout->delete_layout_tabs($tabs, $namespace, $channel_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Deprecated Deletes fields from the saved publish layouts
	 *
	 * @access	public
	 * @param	array or string
	 * @param	int
	 * @return	bool
	 */
	function delete_layout_fields($tabs, $channel_id = array())
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6', 'Layout::delete_layout_fields()');

		ee()->load->library('layout');
		return ee()->layout->delete_layout_fields($tabs, $channel_id);
	}

	// --------------------------------------------------------------------

	/**
	 * URL to the current page unless POST data exists - in which case it
	 * goes to the root controller.  To use the result, prefix it with BASE.AMP
	 *
	 * @access	public
	 * @return	string
	 */
	function get_safe_refresh()
	{
		static $url = '';

		if ( ! $url)
		{
			$go_to_c = (count($_POST) > 0);
			$page = '';

			foreach($_GET as $key => $val)
			{
				if ($key == 'S' OR $key == 'D' OR ($go_to_c && $key != 'C'))
				{
					continue;
				}

				$page .= $key.'='.$val.AMP;
			}

			if (strlen($page) > 4 && substr($page, -5) == AMP)
			{
				$page = substr($page, 0, -5);
			}

			$url = $page;
		}

		return $url;
	}

	// --------------------------------------------------------------------

	/**
	 * 	Get Quicklinks
	 *
	 * 	Does a lookup for quick links.  Based on the URL we determine if it is external or not
	 *
	 * 	@access private
	 * 	@return array
	 */
	function _get_quicklinks($quick_links)
	{
		$i = 1;

		$quicklinks = array();

		if (count($quick_links) != 0 && $quick_links != '')
		{
			foreach (explode("\n", $quick_links) as $row)
			{
				$x = explode('|', $row);

				$quicklinks[$i]['title'] = (isset($x[0])) ? $x[0] : '';
				$quicklinks[$i]['link'] = (isset($x[1])) ? $x[1] : '';
				$quicklinks[$i]['order'] = (isset($x[2])) ? $x[2] : '';

				$i++;
			}
		}

		$quick_links = $quicklinks;

		$len = strlen(ee()->config->item('cp_url'));

		$link = array();

		$count = 0;

		foreach ($quick_links as $ql)
		{
			if (strncmp($ql['link'], ee()->config->item('cp_url'), $len) == 0)
			{
				$l = str_replace(ee()->config->item('cp_url'), '', $ql['link']);
				$l = preg_replace('/\?S=[a-zA-Z0-9]+&D=cp&/', '', $l);

				$link[$count] = array(
					'link'		=> BASE.AMP.$l,
					'title'		=> $ql['title'],
					'external'	=> FALSE
				);
			}
			else
			{
				$link[$count] = array(
					'link'		=> $ql['link'],
					'title'		=> $ql['title'],
					'external'	=> TRUE
				);
			}

			$count++;
		}

		return $link;
	}

	// --------------------------------------------------------------------

	/**
	 * Abstracted Way to Add a Page Variable
	 *
	 * @access	public
	 * @return	void
	 */
	function set_variable($name, $value)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6', 'view-><var> = <value>;');

		// workaround for setting globals
		ee()->load->vars($name, $value);

		// the future!
		ee()->view->$name = $value;
	}

	// --------------------------------------------------------------------

	/**
	 * Abstracted Way to Add a Breadcrumb Links
	 *
	 * @access	public
	 * @return	void
	 */
	function set_breadcrumb($link, $title)
	{
		static $_crumbs = array();

		$_crumbs[$link] = $title;
		ee()->view->cp_breadcrumbs = $_crumbs;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate and Enable Secure Forms for the Control Panel
	 *
	 * @deprecated 2.6
	 * @access	public
	 * @return	void
	 */
	function secure_forms()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6', 'EE_Security::have_valid_xid()');

		$hash = '';

		if (ee()->config->item('secure_forms') == 'y')
		{
			if (count($_POST) > 0)
			{
				if ( ! isset($_POST['XID'])
					OR ! ee()->security->secure_forms_check($_POST['XID']))
				{
					ee()->functions->redirect(BASE);
				}

				unset($_POST['XID']);
			}

			$hash = ee()->security->generate_xid();
		}

		define('XID_SECURE_HASH', $hash);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch CP Themes
	 *
	 * Fetch control panel themes
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function fetch_cp_themes()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6', 'Admin_model::get_cp_theme_list()');

		ee()->load->model('admin_model');
		return ee()->admin_model->get_cp_theme_list();
	}

	// --------------------------------------------------------------------

	/**
	 * Load Package JS
	 *
	 * Load a javascript file from a package
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function load_package_js($file)
	{
		$current_top_path = ee()->load->first_package_path();
		$package = trim(str_replace(array(PATH_THIRD, 'views'), '', $current_top_path), '/');
		ee()->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'package='.$package.AMP.'file='.$file, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Load Package CSS
	 *
	 * Load a stylesheet from a package
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function load_package_css($file)
	{
		$current_top_path = ee()->load->first_package_path();
		$package = trim(str_replace(array(PATH_THIRD, 'views'), '', $current_top_path), '/');
		$url = BASE.AMP.'C=css'.AMP.'M=third_party'.AMP.'package='.$package.AMP.'theme='.$this->cp_theme.AMP.'file='.$file;

		$this->add_to_head('<link type="text/css" rel="stylesheet" href="'.$url.'" />');
	}

	// --------------------------------------------------------------------

	/**
	 * Add Header Data
	 *
	 * Add any string to the <head> tag
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function add_to_head($data)
	{
		$this->its_all_in_your_head[] = $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Add Footer Data
	 *
	 * Add any string above the </body> tag
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function add_to_foot($data)
	{
		$this->footer_item[] = $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Allowed Group
	 *
	 * Member access validation
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function allowed_group()
	{
		$which = func_get_args();

		if ( ! count($which))
		{
			return FALSE;
		}

		// Super Admins always have access
		if (ee()->session->userdata('group_id') == 1)
		{
			return TRUE;
		}

		foreach ($which as $w)
		{
			$k = ee()->session->userdata($w);

			if ( ! $k OR $k !== 'y')
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Is Module Installed?
	 *
	 * Returns array of installed modules.
	 *
	 * @access public
	 * @return array
	 */
	function get_installed_modules()
	{
	    if ( ! is_array($this->installed_modules))
	    {
	        $this->installed_modules = array();

	        ee()->db->select('LOWER(module_name) AS name');
	        ee()->db->order_by('module_name');
	        $query = ee()->db->get('modules');

	        if ($query->num_rows())
	        {
				foreach($query->result_array() as $row)
				{
					$this->installed_modules[$row['name']] = $row['name'];
				}
	        }
	    }

	    return $this->installed_modules;
	}

	// --------------------------------------------------------------------

	/**
	 * Invalid Custom Field Names
	 *
	 * Tracks "reserved" words to avoid variable name collision
	 *
	 * @access	public
	 * @return	array
	 */
	function invalid_custom_field_names()
	{
		static $invalid_fields = array();

		if ( ! empty($invalid_fields))
		{
			return $invalid_fields;
		}

		$channel_vars = array(
			'aol_im', 'author', 'author_id', 'avatar_image_height',
			'avatar_image_width', 'avatar_url', 'bday_d', 'bday_m',
			'bday_y', 'bio', 'comment_auto_path',
			'comment_entry_id_auto_path',
			'comment_total', 'comment_url_title_path', 'count',
			'edit_date', 'email', 'entry_date', 'entry_id',
			'entry_id_path', 'expiration_date', 'forum_topic_id',
			'gmt_edit_date', 'gmt_entry_date', 'icq', 'interests',
			'ip_address', 'location', 'member_search_path', 'month',
			'msn_im', 'occupation', 'permalink', 'photo_image_height',
			'photo_image_width', 'photo_url', 'profile_path',
			'recent_comment_date', 'relative_date', 'relative_url',
			'screen_name', 'signature', 'signature_image_height',
			'signature_image_url', 'signature_image_width', 'status',
			'switch', 'title', 'title_permalink', 'total_results',
			'trimmed_url', 'url', 'url_as_email_as_link', 'url_or_email',
			'url_or_email_as_author', 'url_title', 'url_title_path',
			'username', 'channel', 'channel_id', 'yahoo_im', 'year'
		);

		$global_vars = array(
			'app_version', 'captcha', 'charset', 'current_time',
			'debug_mode', 'elapsed_time', 'email', 'embed', 'encode',
			'group_description', 'group_id', 'gzip_mode', 'hits',
			'homepage', 'ip_address', 'ip_hostname', 'lang', 'location',
			'member_group', 'member_id', 'member_profile_link', 'path',
			'private_messages', 'screen_name', 'site_index', 'site_name',
			'site_url', 'stylesheet', 'total_comments', 'total_entries',
			'total_forum_posts', 'total_forum_topics', 'total_queries',
			'username', 'webmaster_email', 'version'
		);

		$orderby_vars = array(
			'comment_total', 'date', 'edit_date', 'expiration_date',
			'most_recent_comment', 'random', 'screen_name', 'title',
			'url_title', 'username', 'view_count_four', 'view_count_one',
			'view_count_three', 'view_count_two'
		);

		$prefixes = array(
			'parents', 'siblings'
		);

		return array_unique(array_merge(
			$channel_vars,
			$global_vars,
			$orderby_vars,
			$prefixes
		));
	}

	// --------------------------------------------------------------------

	/**
	 * 	Fetch Action IDs
	 *
	 * 	@access public
	 *	@param string
	 * 	@param string
	 *	@return mixed
	 */
	function fetch_action_id($class, $method)
	{
		ee()->db->select('action_id');
		ee()->db->where('class', $class);
		ee()->db->where('method', $method);
		$query = ee()->db->get('actions');

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row('action_id');
	}

}

/* End of file Cp.php */
/* Location: ./system/expressionengine/libraries/Cp.php */
