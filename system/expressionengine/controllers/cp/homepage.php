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
		
		/** --------------------------------
		/**  Version Check
		/** --------------------------------*/
		
		if (($ver = $this->_version_check()) !== FALSE)
		{
			if ($ver > APP_VER)
			{
				$qm = ($this->config->item('force_query_string') == 'y') ? '' : '?';	
				$this->messages[] = $this->dsp->qdiv('success', $this->dsp->anchor($this->functions->fetch_site_index().$qm.'URL=https://secure.expressionengine.com/download.php', $line));
			}
		}

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
		$this->_checksum_bootstrap_files();
		
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
				else {
					var loading = $("<img src=\''.$this->config->slash_item('theme_folder_url').'cp_global_images/loader.gif\' />")
							.appendTo(document.body)
							.css({
								"position" : "absolute",
								"left" : that.offset().left - 20,
								"top" : that.offset().top + that.height() / 2 - 8		// offset, center to element, center image
							});
					
					that.siblings(".open").removeClass("open").children("ul").slideUp("fast");

					$.get(url+"&print_redirect", function(response) {

						if (response.substring(0, 9) == "index.php") {
							window.location.href = response;
							return;
						}
						
						loading.hide();

						submenu = $("<ul class=\'submenu\'></ul>");
						instructions = $(response).find(".pageContents h3").text();
						$("<li><p>"+instructions+"</p></li>").appendTo(submenu);

						$(response).find(".pageContents ul li").appendTo(submenu);
						submenu.appendTo(that).hide().slideDown("fast");
						that.addClass("open");
					}, "html");
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
	 * Return recently edited <entry/template>
	 *
	 * Used as a json callback for the sidebar - no point in doing this on
	 * page load everywhere, it's not relevant enough.
	 *
	 * @access	public
	 * @param	type
	 * @return	void
	 */
	function recently_edited()
	{
		// @todo this whole function would be better off in a model, but which one?
		$response = array();

		$this->db->select('entry_id, channel_id');
		$this->db->order_by('edit_date', 'desc');

		if ($this->session->userdata['can_edit_other_entries'] != 'y')
		{
			$this->db->where('author_id', $this->session->userdata['member_id']);
		}

		$this->db->where('site_id', $this->config->item('site_id'));

		$query = $this->db->get('channel_titles', 1);

		if ($query->num_rows() > 0)
		{
			$url = BASE.AMP.'C=content_publish&M=view_entry&channel_id='.$query->row('channel_id').'&entry_id='.$query->row('entry_id');
			$response[$this->lang->line('most_recent_edited_entry')] = $url;
		}

		$this->db->select('template_id');
		$this->db->order_by('edit_date', 'desc');
		$this->db->where('site_id', $this->config->item('site_id'));
				
		$query = $this->db->get('templates', 1);

		if ($query->num_rows() > 0 AND $this->session->userdata['can_admin_templates'] == 'y')
		{
			$url = BASE.AMP.'C=design&M=edit_template&id='.$query->row('template_id');
			$response[$this->lang->line('most_recent_edited_template')] = $url;
		}

		echo $this->javascript->generate_json($response);
		exit;
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
		/** --------------------------------
		/**  Version Check
		/** --------------------------------*/
		
		if ($this->session->userdata['group_id'] == 1 AND $this->config->item('new_version_check') == 'y')
		{
			$page_url = 'http://expressionengine.com/eeversion.txt';
			$target = parse_url($page_url);

			$fp = @fsockopen($target['host'], 80, $errno, $errstr, 3);

			if (is_resource($fp))
			{
				fputs ($fp,"GET ".$page_url." HTTP/1.0\r\n" );
				fputs ($fp,"Host: ".$target['host'] . "\r\n" );
				fputs ($fp,"User-Agent: EE/EllisLab PHP/\r\n");
				fputs ($fp,"If-Modified-Since: Fri, 01 Jan 2004 12:24:04\r\n\r\n");

				$ver = '';

				while ( ! feof($fp))
				{
					$ver .= trim(fgets($fp, 128));
				}

				fclose($fp);

				if ($ver != '')
				{
					return trim(str_replace('Version:', '', strstr($ver, 'Version:')));
				}
			}
			else
			{
				$this->conn_failure = TRUE;
				return FALSE;
			}
		}
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
}

/* End of file homepage.php */
/* Location: ./system/expressionengine/controllers/cp/homepage.php */