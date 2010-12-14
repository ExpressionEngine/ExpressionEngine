<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Cp {
	
	var $cp_theme				= '';
	var $cp_theme_url			= '';	// base URL to the CP theme folder

	var $xid_ttl 				= 14400;
	var $installed_modules		= FALSE;

	var $its_all_in_your_head	= array();
	var $footer_item			= array();
		
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
		
		if ($this->EE->router->fetch_class() == 'ee')
		{
			show_error("The CP library is only available on Control Panel requests.");
		}
		
		// Cannot set these in the installer
		if ( ! defined('EE_APPPATH'))
		{
			$this->cp_theme	= ( ! $this->EE->session->userdata('cp_theme')) ? $this->EE->config->item('cp_theme') : $this->EE->session->userdata('cp_theme'); 
			$this->cp_theme_url = $this->EE->config->slash_item('theme_folder_url').'cp_themes/'.$this->cp_theme.'/';

			$this->EE->load->vars(array(
				'cp_theme_url'	=> $this->cp_theme_url
			));
		}
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
		$js_folder	= ($this->EE->config->item('use_compressed_js') == 'n') ? 'src' : 'compressed';		
		$langfile	= substr($this->EE->router->class, 0, strcspn($this->EE->router->class, '_'));
		
		// Javascript Path Constants
		
		define('PATH_JQUERY', PATH_THEMES.'javascript/'.$js_folder.'/jquery/');
		define('PATH_JAVASCRIPT', PATH_THEMES.'javascript/'.$js_folder.'/');
		define('JS_FOLDER', $js_folder);


		$this->EE->load->library('menu');
		$this->EE->load->library('accessories');
		$this->EE->load->library('javascript', array('autoload' => FALSE));
 		$this->EE->load->helper('url');

		$this->EE->load->model('member_model'); // for screen_name, quicklinks
		
		$this->EE->lang->loadfile($langfile);
		

		// Success/failure messages
		
		$cp_messages = array();
		
		foreach(array('message_failure', 'message_success') as $flash_key)
		{
			if ($message = $this->EE->session->flashdata($flash_key))
			{
				$flash_key = substr($flash_key, 8);
				$cp_messages[$flash_key] = $message;
			}
		}
		
		$cp_table_template = array(
				'table_open'		=> '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">',
				'row_start'			=> '<tr class="even">',
				'row_alt_start'		=> '<tr class="odd">'
		);

		$cp_pad_table_template = $cp_table_template;
		$cp_pad_table_template['table_open'] = '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">';

		$user_q = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'), array('avatar_filename', 'avatar_width', 'avatar_height', 'screen_name'));
		$notepad_content = $this->EE->member_model->get_notepad_content();


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
					'cp_current_site_label'	=> $this->EE->config->item('site_name'),
					'cp_screen_name'		=> $user_q->row('screen_name'),
					'cp_avatar_path'		=> $user_q->row('avatar_filename') ? $this->EE->config->slash_item('avatar_url').$user_q->row('avatar_filename') : '',
					'cp_avatar_width'		=> $user_q->row('avatar_filename') ? $user_q->row('avatar_width') : '',
					'cp_avatar_height'		=> $user_q->row('avatar_filename') ? $user_q->row('avatar_height') : '',
					'cp_quicklinks'			=> $this->_get_quicklinks(),
					
					'EE_view_disable'		=> FALSE,
					'is_super_admin'		=> ($this->EE->session->userdata['group_id'] == 1) ? TRUE : FALSE,	// for conditional use in view files
										
					// Menu
					'cp_menu_items'			=> $this->EE->menu->generate_menu(),
					'cp_accessories'		=> $this->EE->accessories->generate_accessories(),
					
					// Sidebar state (overwritten below if needed)
					'sidebar_state'			=> '',
					'maincontent_state'		=> '',
		);


		if ( ! is_array($this->EE->load->_ci_view_path))
		{
			$file = $this->EE->load->_ci_view_path.'css/advanced.css';
		}
		else
		{
			foreach ($this->EE->load->_ci_view_path as $a_path)
			{
				$file = $a_path.'css/advanced.css';
				
				if (file_exists($file))
				{
					break;
				}
			}
		}
		
		$vars['advanced_css_mtime'] = (file_exists($file)) ? filemtime($file) : FALSE;
		
		
		if ($this->EE->router->method != 'index')
		{
			$this->set_breadcrumb(BASE.AMP.'C='.$this->EE->router->class, $this->EE->lang->line($this->EE->router->class));
		}
		
		if ($this->EE->session->userdata('show_sidebar') == 'n')
		{
			$vars['sidebar_state']		= ' style="display:none"';
			$vars['maincontent_state']	= ' style="width:100%; display:block"';
        }
		
		
		// The base javascript variables that will be available globally through EE.varname
		// this really could be made easier - ideally it would show up right below the main
		// jQuery script tag - before the plugins, so that it has access to jQuery.

		// If you use it in your js, please uniquely identify your variables - or create
		// another object literal:
		// Bad: EE.test = "foo";
		// Good: EE.unique_foo = "bar"; EE.unique = { foo : "bar"};
		
		$js_lang_keys = array(
			'logout_confirm'	=> $this->EE->lang->line('logout_confirm'),
			'logout'			=> $this->EE->lang->line('logout'),
			'search'			=> $this->EE->lang->line('search'),
			'session_timeout'	=> $this->EE->lang->line('session_timeout')
		);
		
		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- login_reminder => y/n  to turn the CP Login Reminder On or Off.  Default is 'y'
        /* -------------------------------------------*/
		
		if ($this->EE->config->item('login_reminder') != 'n')
		{
			$js_lang_keys['session_expiring'] = $this->EE->lang->line('session_expiring');
			$js_lang_keys['username'] = $this->EE->lang->line('username');
			$js_lang_keys['password'] = $this->EE->lang->line('password');
			$js_lang_keys['login'] = $this->EE->lang->line('login');
			
			$this->EE->javascript->set_global(array(
				'SESS_TIMEOUT'		=> $this->EE->session->cpan_session_len * 1000,
				'XID_TIMEOUT'		=> $this->xid_ttl * 1000,
				'SESS_TYPE'			=> $this->EE->config->item('admin_session_type')	
			));			
		}
		
		$this->EE->javascript->set_global(array(
			'BASE'				=> str_replace(AMP, '&', BASE),
			'XID'				=> XID_SECURE_HASH,
			'PATH_CP_GBL_IMG'	=> PATH_CP_GBL_IMG,
			'CP_SIDEBAR_STATE'	=> $this->EE->session->userdata('show_sidebar'),
			'flashdata'			=> $this->EE->session->flashdata,
			'username'			=> $this->EE->session->userdata('username'),
			'router_class'		=> $this->EE->router->class,				// advanced css
			'lang'				=> $js_lang_keys,
			'THEME_URL'			=> $this->cp_theme_url
		));
		
		// Combo-load the javascript files we need for every request

		$js_scripts = array(
						'effect'	=> 'core',
						'ui'		=> array('core', 'widget', 'mouse', 'position', 'sortable', 'dialog'),
						'plugin'	=> array('ee_focus', 'ee_notice', 'ee_txtarea', 'tablesorter'),
						'file'		=> 'cp/global'
		);
		
		if ($this->cp_theme != 'mobile')
		{
			$js_scripts['plugin'][] = 'ee_navigation';
		}
		
		$this->add_js_script($js_scripts);
		$this->_seal_combo_loader();		
		
		$this->EE->load->vars($vars);
		$this->EE->javascript->compile();
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
		return $this->EE->functions->fetch_site_index(0,0).QUERY_MARKER.'URL='.$url;
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
			if ( ! is_string($in_footer))
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
		
		if ($this->EE->extensions->active_hook('cp_js_end') === TRUE)
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
		static $requests = array();
		static $loaded = array();
		
		$str = '';
		$mtimes = array();
		
		$this->js_files = array_map('array_unique', $this->js_files);
		
		foreach($this->js_files as $type => $files)
		{
			if (isset($loaded[$type]))
			{
				$files = array_diff($files, $loaded[$type]);
			}
			
			if (count($files))
			{
				$mtimes[] = $this->_get_js_mtime($type, $files);
				$str .= AMP.$type.'='.implode(',', $files);
			}
		}
				
		if ($str)
		{
			$loaded = array_merge_recursive($loaded, $this->js_files);

			$this->js_files = array(
					'ui'				=> array(),
					'plugin'			=> array(),
					'file'				=> array(),
					'package'			=> array(),
					'fp_module'			=> array()
			);

			$requests[] = $str.AMP.'v='.max($mtimes);
		}
		
		return $requests;
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
		
		$folder = $this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed';
		
		switch($type)
		{
			case 'ui':			$file = PATH_THEMES.'javascript/'.$folder.'/jquery/ui/ui.'.$name.'.js';
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
		$this->EE->load->vars('cp_right_nav', array_reverse($nav));
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
		$this->EE->load->library('layout');
		return $this->EE->layout->delete_layout_tabs($tabs, $namespace, $channel_id);
	}	

	// --------------------------------------------------------------------
	
	/**
	 * Deprecated Add new tabs and associated fields to saved publish layouts
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function add_layout_tabs($tabs = array(), $namespace = '', $channel_id = array())
	{
		$this->EE->load->library('layout');
		$this->EE->layout->add_layout_tabs($tabs, $namespace, $channel_id);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Deprecated Adds new fields to the saved publish layouts, creating the default tab if required
	 *
	 * @access	public
	 * @param	array
	 * @param	int
	 * @return	bool
	 */
	function add_layout_fields($tabs = array(), $channel_id = array())
	{
		$this->EE->load->library('layout');
		return $this->EE->layout->add_layout_fields($tabs, $channel_id);
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
		$this->EE->load->library('layout');
		return $this->EE->layout->delete_layout_fields($tabs, $channel_id);
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
	function _get_quicklinks()
	{
		$quick_links = $this->EE->member_model->get_member_quicklinks($this->EE->session->userdata('member_id'));
		
		$len = strlen($this->EE->config->item('cp_url'));
		
		$link = array();
		
		$count = 0;
		
		foreach ($quick_links as $ql)
		{
			if (strncmp($ql['link'], $this->EE->config->item('cp_url'), $len) == 0)
			{
				$link[$count]['link'] = str_replace($this->EE->config->item('cp_url'), '', $ql['link']);
				$link[$count]['link'] = preg_replace('/\?S=[a-zA-Z0-9]+&D=cp&/', '', $link[$count]['link']);
				$link[$count]['link'] = BASE.AMP.$link[$count]['link'];
				$link[$count]['title'] = $ql['title'];
				$link[$count]['external'] = FALSE;
			}
			else
			{
				$link[$count]['link'] = $ql['link'];
				$link[$count]['title'] = $ql['title'];
				$link[$count]['external'] = TRUE;
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
		$this->EE->load->vars(array($name => $value));
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
		
		$this->EE->load->vars(array('cp_breadcrumbs' => $_crumbs));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Validate and Enable Secure Forms for the Control Panel
	 *
	 * @access	public
	 * @return	void
	 */		
	function secure_forms()
	{
		$hash = '';
		
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			if (count($_POST) > 0)
			{
				if ( ! isset($_POST['XID']))
				{
					$this->EE->functions->redirect(BASE);
				}
				
				$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes 
												 WHERE hash = '".$this->EE->db->escape_str($_POST['XID'])."' 
												 AND ip_address = '".$this->EE->input->ip_address()."' 
												 AND date > UNIX_TIMESTAMP()-".$this->xid_ttl);
	
				if ($query->row('count')  == 0)
				{
					$this->EE->functions->redirect(BASE);
				}
				else
				{
					$this->EE->db->query("DELETE FROM exp_security_hashes 
											WHERE date < UNIX_TIMESTAMP()-{$this->xid_ttl}
											AND ip_address = '".$this->EE->input->ip_address()."'");
								
					unset($_POST['XID']);
				}
			}
			
			$hash = $this->EE->functions->random('encrypt');
			$this->EE->db->query("INSERT INTO exp_security_hashes (date, ip_address, hash)
								VALUES 
								(UNIX_TIMESTAMP(), '".$this->EE->input->ip_address()."', '".$hash."')");
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
		$this->EE->load->model('admin_model');
		return $this->EE->admin_model->get_cp_theme_list();
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
		$package = trim(str_replace(array(PATH_THIRD, 'views'), '', $this->EE->load->_ci_view_path), '/');
		$this->EE->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'package='.$package.AMP.'file='.$file, TRUE);
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
		$package = trim(str_replace(array(PATH_THIRD, 'views'), '', $this->EE->load->_ci_view_path), '/');
		$url = BASE.AMP.'C=css'.AMP.'M=third_party'.AMP.'package='.$package.AMP.'file='.$file;
		
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
	function allowed_group($which = '')
	{
		if ($which == '')
		{
			return FALSE;
		}	
		
		// Super Admins always have access					
		if ($this->EE->session->userdata['group_id'] == 1)
		{
			return TRUE;
		}
		
		if ( ! isset($this->EE->session->userdata[$which]) OR $this->EE->session->userdata[$which] !== 'y')
		{
			return FALSE;			
		}
		else
		{
			return TRUE;			
		}
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

	        $this->EE->db->select('LOWER(module_name) AS name');
	        $this->EE->db->order_by('module_name');
	        $query = $this->EE->db->get('modules');

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
						
		$invalid_fields = array_unique(array_merge($channel_vars, $global_vars, $orderby_vars));
		return $invalid_fields;
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
		$this->EE->db->select('action_id');
		$this->EE->db->where('class', $class);
		$this->EE->db->where('method', $method);
		$query = $this->EE->db->get('actions');
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		return $query->row('action_id');
	}

}

/* End of file Cp.php */
/* Location: ./system/expressionengine/libraries/Cp.php */