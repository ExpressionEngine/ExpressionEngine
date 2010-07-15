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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Homepage extends Controller {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Homepage()
	{
		parent::Controller();
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */	
	function index($message = '')
	{	
		$this->cp->get_installed_modules();
		$this->cp->set_variable('cp_page_title', $this->lang->line('main_menu'));

		$version			= FALSE;
		$show_notice		= $this->_checksum_bootstrap_files();
		$allowed_templates	= $this->session->userdata('assigned_template_groups');

		// Notices only show for super admins
		if ($this->session->userdata['group_id'] == 1 AND $this->config->item('new_version_check') == 'y')
		{
			$version		= $this->_version_check();
			$show_notice	= ($show_notice OR $version);
		}
		
		$vars = array(
			'version'			=> $version,
			'message'			=> $message,
			'instructions'		=> $this->lang->line('select_channel_to_post_in'),
			'show_page_option'	=> (isset($this->cp->installed_modules['pages'])) ? TRUE : FALSE,
			'info_message_open'	=> ($this->input->cookie('home_msg_state') != 'closed' && $show_notice) ? TRUE : FALSE,
			'no_templates'		=> sprintf($this->lang->line('no_templates_available'), BASE.AMP.'C=design'.AMP.'M=new_template_group'),
			
			'can_access_modify'		=> TRUE,
			'can_access_content'	=> TRUE,
			'can_access_templates'	=> (count($allowed_templates) > 0 && $this->cp->allowed_group('can_access_design')) ? TRUE : FALSE
		);
		
		
		// Pages module is installed, need to check perms
		// to see if the member group can access it.
		// Super admin sees all.
		
		if ($vars['show_page_option'] && $this->session->userdata('group_id') != 1)
		{
			$this->load->model('member_model');
			$vars['show_page_option'] = $this->member_model->can_access_module('pages');
		}
		

		// A few more permission checks
		
		if ( ! $this->cp->allowed_group('can_access_publish'))
		{
			$vars['show_page_option'] = FALSE;
			
			if ( ! $this->cp->allowed_group('can_access_edit') && ! $this->cp->allowed_group('can_admin_templates'))
			{
				$vars['can_access_modify'] = FALSE;
				
				if ( ! $this->cp->allowed_group('can_admin_channels')  && ! $this->cp->allowed_group('can_admin_sites'))
				{
					$vars['can_access_content'] = FALSE;
				}
			}
		}
		
		
		// Most recent comment and most recent entry
		
		$this->load->model('channel_model');
		$comments_installed = $this->db->table_exists('comments');
		
		$vars['cp_recent_ids'] = array(
			'entry'		=> $this->channel_model->get_most_recent_id('entry'),
			'comment'	=> $comments_installed ? $this->channel_model->get_most_recent_id('comment') : FALSE
		);
		
		
		// Prep js
		
		$this->javascript->set_global('lang.close', $this->lang->line('close'));
		
		if ($show_notice)
		{
			$this->javascript->set_global('importantMessage.state', $vars['info_message_open']);
		}

		$this->cp->add_js_script('file', 'cp/homepage');
		$this->javascript->compile();
		
		$this->load->view('homepage', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Accept Bootstrap Checksum Changes
	 * 
	 * Updates the bootstrap file checksums with the new versions.
	 *
	 * @access	public
	 */
	function accept_checksums()
	{
		if ($this->session->userdata('group_id') != 1)
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->library('file_integrity');
		$changed = $this->file_integrity->check_bootstrap_files(TRUE);

		if ($changed)
		{
			foreach($changed as $site_id => $paths)
			{
				foreach($paths as $path)
				{
					$this->file_integrity->create_bootstrap_checksum($path, $site_id);
				}
			}
		}
		
		$this->functions->redirect(BASE.AMP.'C=homepage');
	}

	// --------------------------------------------------------------------

	/**
	 * Bootstrap Checksum Validation
	 * 
	 * Creates a checksum for our bootstrap files and checks their
	 * validity with the database
	 *
	 * @access	private
	 */
	function _checksum_bootstrap_files()
	{
		$this->load->library('file_integrity');
		$changed = $this->file_integrity->check_bootstrap_files();

		if ($changed)
		{
			// Email the webmaster - if he isn't already looking at the message
			
			if ($this->session->userdata('email') != $this->config->item('webmaster_email'))
			{
				$this->file_integrity->send_site_admin_warning($changed);
			}
			
			if ($this->session->userdata('group_id') == 1)
			{
				$this->load->vars(array('new_checksums' => $changed));
				return TRUE;
			}
		}
		
		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * EE Version Check function
	 * 
	 * Requests a file from ExpressionEngine.com that informs us what the current available version
	 * of ExpressionEngine.  In the future, we might put the build number in there as well.
	 *
	 * @access	private
	 * @return	bool|string
	 */
	function _version_check()
	{
		// Attempt to grab the local cached file
		$cached = $this->_check_version_cache();

		$download_url = $this->cp->masked_url('https://secure.expressionengine.com/download.php');

		$ver = '';
		
		// Is the cache current?
		if ( ! $cached)
		{
			$details['timestamp'] = time();
			
			$dl_page_url = 'http://expressionengine.com/eeversion2.txt';
			$target = parse_url($dl_page_url);
			
			$fp = @fsockopen($target['host'], 80, $errno, $errstr, 3);
			
			if (is_resource($fp))
			{
				fputs ($fp,"GET ".$dl_page_url." HTTP/1.0\r\n" );
				fputs ($fp,"Host: ".$target['host'] . "\r\n" );
				fputs ($fp,"User-Agent: EE/EllisLab PHP/\r\n");
				fputs ($fp,"If-Modified-Since: Fri, 01 Jan 2004 12:24:04\r\n\r\n");
				
				while ( ! feof($fp))
				{
					$ver .= trim(fgets($fp, 128));
				}

				fclose($fp);
				
				if ($ver != '')
				{
					$details['version'] = trim(str_replace('Version:', '', strstr($ver, 'Version:')));
				
					if ($details['version'] != '')
					{
						// We have the version from ExpressionEngine.com, write a cache file
						$this->_write_version_cache($details);						
					}
					else
					{
						// Something went wrong.
						unset($details['version']);

						$details['error'] = TRUE;
						$this->_write_version_cache($details);						
					}
				}
				else
				{
					$details['error'] = TRUE;
					$this->_write_version_cache($details);
				}
			}
			else
			{
				// Something went wrong.
				$details['error'] = TRUE;
				
				$this->_write_version_cache($details);	
			}
		}
		else
		{
			$details = $cached;
		}
		
		if (isset($details['version']))
		{
			if ($details['version'] > APP_VER)
			{
				return sprintf($this->lang->line('new_version_notice'),
							   $details['version'],
							   $download_url,
							   $this->cp->masked_url($this->config->item('doc_url').'installation/update.html'));
			}
		}
		else
		{
			return sprintf($this->lang->line('new_version_error'),
							$download_url);
		}
		
		return FALSE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Check EE Version Cache.
	 *
	 * @access	private
	 * @return	bool|string
	 */
	function _check_version_cache()
	{
		// check cache first
		$cache_expire = 60 * 60 * 24;	// only do this once per day
		$this->load->helper('file');	
		$contents = read_file(APPPATH.'cache/ee_version/current_version');

		if ($contents !== FALSE)
		{
			$details = unserialize($contents);

			if (($details['timestamp'] + $cache_expire) > $this->localize->now)
			{
				return $details;
			}
			else
			{
				return FALSE;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Write EE Version Cache
	 *
	 * @param array - details of version needed to be cached.
	 * @return void
	 */
	function _write_version_cache($details)
	{
		$this->load->helper('file');
		
		if ( ! is_dir(APPPATH.'cache/ee_version'))
		{
			mkdir(APPPATH.'cache/ee_version', DIR_WRITE_MODE);
			@chmod(APPPATH.'cache/ee_version', DIR_WRITE_MODE);	
		}

		if (write_file(APPPATH.'cache/ee_version/current_version', serialize($details)))
		{
			@chmod(APPPATH.'cache/ee_version/current_version', FILE_WRITE_MODE);			
		}		
	}
}

/* End of file homepage.php */
/* Location: ./system/expressionengine/controllers/cp/homepage.php */