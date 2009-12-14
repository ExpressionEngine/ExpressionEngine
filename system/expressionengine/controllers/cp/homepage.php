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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Homepage extends Controller {

	function Homepage()
	{
		// Call the Controller constructor.  
		// Without this, the world as we know it will end!
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized
		// automatically via the autoload.php file.  If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}
		
		/** --------------------------------
		/**  Does the install folder exist?
		/** --------------------------------*/
		
		// If so, we will issue a warning  
		
		if ($this->config->item('demo_date') === FALSE && is_dir(BASEPATH.'expressionengine/installer'))
		{
			$this->messages[] = $this->dsp->qdiv('alert', $this->lang->line('install_lock_warning'));
			$this->messages[] = $this->dsp->qdiv('itemWrapper', $this->lang->line('install_lock_removal'));
		}

		/** --------------------------------
		/**  Demo account expiration
		/** --------------------------------*/
		
		// We use this code for expressionengine.com demos.
		// Since it's only a couple lines of code we'll leave it in 
		// the master files even though it's not needed for normal use.

		if ($this->config->item('demo_date'))
		{
			$expiration = ( ! $this->config->item('demo_expiration')) ? (60*60*24*30) : $this->config->item('demo_expiration');
			$this->messages[] = $this->dsp->qdiv('itemWrapper', $this->dsp->qspan('defaultBold', $this->lang->line('demo_expiration').NBS.NBS.$this->localize->format_timespan(($this->config->item('demo_date') + $expiration) - time())));
		}
		// -- End Demo Code
		
		

		$this->load->vars(array('cp_page_id'=>'homepage'));
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 * 
	 * Every controller must have an index function, which gets called
	 * automatically by CodeIgniter when the URI does not contain a call to
	 * a specific method call
	 *
	 * @access	public
	 * @return	mixed
	 */	
	function index($message = '')
	{
		$vars['version'] = FALSE;


		$this->_checksum_bootstrap_files();
		
		
		if ($this->session->userdata['group_id'] == 1 AND $this->config->item('new_version_check') == 'y')
		{
			$vars['version'] = $this->_version_check();
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('main_menu'));
		
		$this->javascript->output('
		$("<div id=\"ajaxContent\"></div>").dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			position: "center",
			minHeight: "0px", // fix display bug, where the height of the dialog is too big
			buttons: { "'.$this->lang->line('close').'": function() { $(this).dialog("close"); } }
		});

		$("a.submenu").click(function() {
			if ($(this).data("working")) {
				return false;
			}
			else {
				$(this).data("working", true);
			}
			
			var url = $(this).attr("href"),
				that = $(this).parent(),
				submenu = that.find("ul");
	
			if ($(this).hasClass("accordion")) {
				
				if (submenu.length > 0) {
					if ( ! that.hasClass("open")) {
						that.siblings(".open").toggleClass("open").children("ul").slideUp("fast");
					}

					submenu.slideToggle("fast");
					that.toggleClass("open");
				}
				
				$(this).data("working", false);
			}
			else {
				$(this).data("working", false);
				var dialog_title = $(this).html();

				$("#ajaxContent").load(url+" .pageContents", function() {
					$("#ajaxContent").dialog("option", "title", dialog_title);
					$("#ajaxContent").dialog("open");
				});
			}

			return false;
		});
		');
		
		$vars['msg_class'] = ($this->input->cookie('home_msg_state') == 'closed') ? 'closed' : 'open';		
		$vars['open_close_msg'] = $vars['msg_class'];

		// Ignore version update javascript
		$this->javascript->output('
			var messageBoxState = "'.$vars['msg_class'].'";
			
			var messageContainer = $("#ee_important_message");
		
			$("#ee_homepage_notice .msg_open_close").click( function() {
				if (messageBoxState == "open") {
					messageBoxState = "closed";
				} else if (messageBoxState == "closed") {
					messageBoxState = "open";
				}

				$.ajax({
					url: "'.str_replace("&amp;", "&", BASE.AMP."C=homepage&M=hide_message_box").'",
					data: "state="+messageBoxState,
					cache: true,
					success: collapseHomepageNotice(messageContainer, messageBoxState)
				});
			});
		
			function collapseHomepageNotice(messageContainer, messageBoxState)
			{	
				$("#ee_important_message").hide();
	
				if (messageBoxState == "open") {
					$(messageContainer).removeClass("closed");
					$(messageContainer).addClass("open");
					$("#noticeContents").show();
				} else if (messageBoxState == "closed"){
					$(messageContainer).removeClass("open");
					$(messageContainer).addClass("closed");
					$("#noticeContents").hide();
				}
				
				$("#ee_important_message").fadeIn("slow");
			}
		
		');

		$this->javascript->compile();

        $vars['instructions'] = $this->lang->line('select_channel_to_post_in');
		$vars['message'] = $message;

		$vars['can_access_content'] = TRUE;
		$vars['can_access_modify'] = TRUE;		

		if ( ! $this->cp->allowed_group('can_access_publish') && ! $this->cp->allowed_group('can_access_edit') && ! $this->cp->allowed_group('can_admin_templates') && ! $this->cp->allowed_group('can_admin_channels')  && ! $this->cp->allowed_group('can_admin_sites'))
		{
			$vars['can_access_content'] = FALSE;
		}

		if ( ! $this->cp->allowed_group('can_access_publish') && ! $this->cp->allowed_group('can_access_edit') && ! $this->cp->allowed_group('can_admin_templates'))
		{
			$vars['can_access_modify'] = FALSE;
		}

		$allowed_templates = $this->session->userdata('assigned_template_groups');
		$vars['can_access_templates'] = (count($allowed_templates) > 0 && $this->cp->allowed_group('can_access_design')) ? TRUE : FALSE;

		$this->load->model('addons_model');
		
		$vars['show_page_option'] = $this->addons_model->module_installed('pages');

		if ( ! $this->cp->allowed_group('can_access_publish'))	
		{
			$vars['show_page_option'] = FALSE;
		}

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
			if ($this->session->userdata('group_id') == 1)
			{
				$this->load->vars(array('new_checksums' => $changed));
			}
			
			// Email the webmaster - if he isn't already looking at the message
			
			if ($this->session->userdata('email') != $this->config->item('webmaster_email'))
			{
				$this->file_integrity->send_site_admin_warning($changed);
			}
		}
	}

	// --------------------------------------------------------------------

	
	function hide_message_box()
	{
		$msg_state = $this->input->get_post('state');
		
		$this->functions->set_cookie('home_msg_state', "{$msg_state}", 86400);
		exit;
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

		$download_url = $this->functions->fetch_site_index().QUERY_MARKER.'URL=https://secure.expressionengine.com/download.php';

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
		
		$vars['message'] = FALSE;
		
		if (isset($details['version']))
		{
			if (($details['version'] > APP_VER))
			{
				return sprintf($this->lang->line('new_version_notice'),
							   $details['version'],
							   $download_url,
							   $this->config->item('doc_url').'installation/update.html');
			}
		}
		else 
		{
			return sprintf($this->lang->line('new_version_error'),
							$download_url);
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Check EE Version Cache.  
	 *
	 * 
	 *
	 */
	function _check_version_cache()
	{
		// check cache first
		//$cache_expire = 60 * 60 * 24;	// only do this once per day
		$cache_expire = 60;
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
		}

		write_file(APPPATH.'cache/ee_version/current_version', serialize($details));
	}
}

/* End of file homepage.php */
/* Location: ./system/expressionengine/controllers/cp/homepage.php */