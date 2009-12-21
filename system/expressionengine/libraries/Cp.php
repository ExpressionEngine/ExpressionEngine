<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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
	
	var $cp_theme_url = '';	// base URL to the CP theme folder
	var $its_all_in_your_head = array();
	var $js_files    = array(
						'ui'        => array(),
						'effect'    => array(),
						'plugin'    => array(),
						'file'      => array(),
						'package'   => array(),
	);

	var $footer_item = array();
	
	var $installed_modules = FALSE;
	
	/**
	 * Constructor
	 *
	 */	
	function Cp()
	{
		$this->EE =& get_instance();
		
		if ($this->EE->router->class == 'ee')
		{
			show_error("The CP library is only available on Control Panel requests.");
		}
		
		// @confirm uhhh.. what?
		$this->EE->load->vars(array('cp_page_id' => 'Whatever'));
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
		$cp_theme	= ( ! $this->EE->session->userdata('cp_theme')) ? $this->EE->config->item('cp_theme') : $this->EE->session->userdata('cp_theme'); 
		$js_folder	= ($this->EE->config->item('use_compressed_js') == 'n') ? 'src' : 'compressed';		
		$langfile	= substr($this->EE->router->class, 0, strcspn($this->EE->router->class, '_'));
		
		$this->cp_theme_url = $this->EE->config->slash_item('theme_folder_url').'cp_themes/'.$cp_theme.'/';
		
		$this->EE->load->library('menu');
		$this->EE->load->library('accessories');
		$this->EE->load->library('javascript', array('autoload' => FALSE));
 		$this->EE->load->helper('url');

		$this->EE->load->model('member_model'); // for screen_name, quicklinks
		$this->EE->load->model('channel_model'); // for most recent entry/comment quicklinks
		
		$this->EE->lang->loadfile($langfile);
		
		
		// Javascript Path Constants
		
		define('PATH_JQUERY', APPPATH.'javascript/'.$js_folder.'/jquery/');
		define('PATH_JAVASCRIPT', APPPATH.'javascript/'.$js_folder.'/');
		
		
		// Most recent comment and most recent entry
		// (@confirm not needed on every page)
		
		$comments_installed = $this->EE->db->table_exists('comments');
		
		$recent = array(
			'entry'		=> $this->EE->channel_model->get_most_recent_id('entry'),
			'comment'	=> $comments_installed ? $this->EE->channel_model->get_most_recent_id('comment') : FALSE
		);
		

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


		// Table templates

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
					'cp_page_id'			=> '',
					'cp_page_onload'		=> '',
					'cp_page_title'			=> '',
					'cp_breadcrumbs'		=> array(),
					'cp_right_nav'			=> array(),
					'cp_recent_ids'			=> $recent,
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
					'cp_quicklinks'			=> $this->EE->member_model->get_member_quicklinks($this->EE->session->userdata('member_id')),
					
					'EE_view_disable'		=> FALSE,
					'is_super_admin'		=> ($this->EE->session->userdata['group_id'] == 1) ? TRUE : FALSE,	// for conditional use in view files
										
					// Menu
					'cp_menu_items'			=> $this->EE->menu->generate_menu(),
					'cp_accessories'		=> $this->EE->accessories->generate_accessories(),
					
					// Sidebar state (overwritten below if needed)
					'sidebar_state'			=> '',
					'maincontent_state'		=> '',

					// Asset mtimes to force caching
					'jquery_mtime' 		=> filemtime(PATH_JQUERY.'jquery.js'),
					'corner_mtime' 		=> filemtime(PATH_JQUERY.'plugins/corner.js'),
					'theme_css_mtime'	=> filemtime(PATH_CP_THEME.$cp_theme.'/css/global.css'),
					'global_js_mtime'	=> filemtime(PATH_JAVASCRIPT.'cp/global.js')
		);


		if (file_exists(PATH_CP_THEME.$cp_theme.'/css/advanced.css'))
		{
			$vars['advanced_css_mtime'] = filemtime(PATH_CP_THEME.$cp_theme.'/css/advanced.css');
		}
		
		if ($this->EE->router->method != 'index')
		{
			$this->set_breadcrumb(BASE.AMP.'C='.$this->EE->router->class, $this->EE->lang->line($this->EE->router->class));
		}
		
		if ($this->EE->input->cookie('cp_sidebar_state') == 'off')
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
			'logout'			=> $this->EE->lang->line('logout')
		);
		
		$this->EE->javascript->set_global(array(
			'BASE'				=> str_replace(AMP, '&', BASE),
			'XID'				=> (defined('XID_SECURE_HASH')) ? XID_SECURE_HASH : "",
			'PATH_CP_GBL_IMG'	=> PATH_CP_GBL_IMG,
			'CP_SIDEBAR_STATE'	=> ($this->EE->input->cookie('cp_sidebar_state') == 'off') ? 'off' : 'on',
			'flashdata'			=> $this->EE->session->flashdata,
			'username'			=> $this->EE->session->userdata('username'),
			'router_class'		=> $this->EE->router->class,				// advanced css
			'lang'				=> $js_lang_keys
		));
		
		// Combo-load the javascript files we need for every request

		$js_scripts = array(
						'ui'		=> array('core', 'sortable', 'dialog'),
						'file'		=> array('ee_txtarea'),
						'plugin'	=> array('ee_focus', 'ee_notice')
		);
		
		if ($cp_theme != 'mobile')
		{
			$js_scripts['plugin'][] = 'ee_navigation';
		}
		
		$this->add_js_script($js_scripts);
		
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
		if ( ! $url)
		{
			return FALSE;
		}
		
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
	 *
	 *
	 */
	function add_js_script($script = array(), $in_footer = TRUE)
	{
		if ( ! is_array($script))
		{
			return FALSE;
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
		// jquery Ui stuff
		$ui = array_unique($this->js_files['ui']);
		$ui = ($ui) ? AMP.'ui='.implode(',', $ui) : '';

		// File
		$file = array_unique($this->js_files['file']); 
		$file = ($file) ? AMP.'file='.implode(',', $file) : '';

		// Plugins
		$plugin = array_unique($this->js_files['plugin']);
		$plugin = ($plugin) ? AMP.'plugin='.implode(',', $plugin) : '';

		return '<script type="text/javascript" charset="utf-8" src="'.BASE.AMP.'C=javascript'.AMP.'M=combo_load'.$ui.$plugin.$file.'"></script>';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set the right navigation
	 *
	 * @access	private
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
	 * Add new tabs and associated fields to saved publish layouts
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function add_layout_tabs($tabs = array())
	{
		if ( ! is_array($tabs) OR count($tabs) == 0)
		{
			return FALSE;
		}

		$this->EE->load->model('member_model');
		$this->EE->member_model->update_layouts($tabs, 'add_tabs');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Updates saved publish layouts
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function delete_layout_tabs($tabs = array())
	{
		if ( ! is_array($tabs) OR count($tabs) == 0)
		{
			return FALSE;
		}

		$this->EE->load->model('member_model');
		
		return $this->EE->member_model->update_layouts($tabs, 'delete_tabs');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Adds new fields to the saved publish layouts, creating the default tab if required
	 *
	 * @access	public
	 * @param	array
	 * @param	int
	 * @return	bool
	 */
	function add_layout_fields($tabs = array(), $channel_id = array())
	{
		if ( ! is_array($channel_id))
		{
			$channel_id = array($channel_id);
		}
		
		if ( ! is_array($tabs) OR count($tabs) == 0)
		{
			return FALSE;
		}

		$this->EE->load->model('member_model');
		
		return $this->EE->member_model->update_layouts($tabs, 'add_fields', $channel_id);
	}
	

	// --------------------------------------------------------------------
	
	/**
	 * Deletes fields from the saved publish layouts
	 *
	 * @access	public
	 * @param	array or string
	 * @param	int
	 * @return	bool
	 */
	function delete_layout_fields($tabs, $channel_id = array())
	{
		if ( ! is_array($channel_id))
		{
			$channel_id = array($channel_id);
		}

		if ( ! is_array($tabs))
		{
			$tabs = array($tabs);
		}
		
		$this->EE->load->model('member_model');
	
		return $this->EE->member_model->update_layouts($tabs, 'delete_fields', $channel_id);
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
												 AND date > UNIX_TIMESTAMP()-14400");
	
				if ($query->row('count')  == 0)
				{
					$this->EE->functions->redirect(BASE);
				}
				else
				{
					$this->EE->db->query("DELETE FROM exp_security_hashes 
											WHERE date < UNIX_TIMESTAMP()-14400
											AND ip_address = '".$this->EE->input->ip_address()."'");
								
					unset($_POST['XID']);
				}
			}
			
			$hash = $this->EE->functions->random('encrypt');
			$this->EE->db->query("INSERT INTO exp_security_hashes (date, ip_address, hash)
								VALUES 
								(UNIX_TIMESTAMP(), '".$this->EE->input->ip_address()."', '".$hash."')");
			
			define('XID_SECURE_HASH', $hash);
		}
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
	 * @pk Hack for Brandon. Check with DJ -- css controller, <link> tag, filemtime caching, documentation
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function load_package_css($file)
	{
		$package = realpath($this->EE->load->_ci_view_path.'../');
		$this->add_to_head('<style type="text/css" media="screen">'.
			file_get_contents($package.'/css/'.$file.'.css').
		'</style>');
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