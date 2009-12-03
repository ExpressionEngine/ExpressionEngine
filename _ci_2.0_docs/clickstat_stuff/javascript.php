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
 * ExpressionEngine CP CSS Loading Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Javascript extends Controller {

	function Javascript()
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

		if ( ! defined('PATH_JQUERY'))
		{
			if ($this->config->item('use_compressed_js') == 'n')
			{
				define('PATH_JQUERY', APPPATH.'javascript/src/jquery/');
			}
			else
			{
				define('PATH_JQUERY', APPPATH.'javascript/compressed/jquery/');
			}
		}

		$this->lang->loadfile('jquery');
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
	function index()
	{
		// profiler and script that is added to CP controllers by default
		// needs to be nullified to avoid duplicate code and errors
		$this->output->enable_profiler(FALSE);
		$this->javascript->clear_compile();

		@header("Content-type: text/javascript");

		if ($this->config->item('send_headers') == 'y')
		{
			$this->output->set_status_header(200);
			@header('Cache-Control: max-age=86400, must-revalidate');
//			@header('Content-Length: '.strlen($contents)); // @confirm: Jones, what's your vision with $contents
		}
		
		// puts all declared events into output.  I still don't have a good way around this
		// I guess a destructor would work nice... but PHP is crappy at this.
		// @todo look for a way to not need to call this.

		$this->javascript->output(array(
			$this->jquery->corner("ul#navigationTabs li a", "5px"),
			$this->jquery->corner("#logOut a.logOutButton", "5px"),
			$this->jquery->corner(".contentMenu", "top"),
			$this->jquery->corner("#search form"),
			$this->jquery->corner(".contents .heading", "top"),
			$this->jquery->corner(".contents .tableFooter", "bottom"),
			$this->javascript->hide("#revealSidebarLink"),
			$this->jquery->corner("#accessoryTabs li", "top"),
			'$("#navigationTabs a:first").unbind()'
		));

		$this->javascript->click('#hideSidebarLink', array(
													'$(this).hide().siblings(":not(#activeSite, #revealSidebarLink)").slideToggle();',
													$this->javascript->show("#revealSidebarLink"),
													$this->javascript->animate("#mainContent", array('width'=>'100%'))
													)
		);

		$this->javascript->click('#revealSidebarLink', array(
													'$(this).hide().siblings(":not(#activeSite, #revealSidebarLink)").slideToggle();',
													$this->javascript->show("#hideSidebarLink"),
													$this->javascript->animate("#mainContent", array('width'=>'77%'))
													)
		);

		$this->javascript->click('#navigationTabs > li.parent > a', '
			// absolute positioning and negative "left" attribute being used so submenus
			// remain accessible at all times to users of screen reading software

			// send all submenus off into the black
			$(this).parent().siblings().children("ul").css( { left:"-9999px" });

			// toggle the desired submenu
			if ($(this).parent().children("ul").css("left") == "0px")
			{
				$(this).parent().children("ul").css( { left:"-9999px" });
			}
			else
			{
				$(this).parent().children("ul").css( { left:"0px" });
			}
		');

		$this->javascript->click('#accessoryTabs li a', array(
													'$(this).parent("li").siblings().removeClass("current");',
													'$(this).parent("li").addClass("current");',
													'$("#" + this.className).slideToggle().siblings(":not(#accessoryTabs)").hide();'
													)
		);
		
		$this->javascript->output("
			var search = $('#sideBar #search'),
				result = search.clone(),
				buttonImgs = $('.searchButton', '#cp_search_form');

			submit_handler = function() {
				var url = $(this).attr('action');
				var data = {
					'cp_search_keywords': $('#cp_search_keywords').attr('value')
				};
				
				$.ajax({
					url: url+'&ajax=y',
					data: data,
					beforeSend: function() {
						buttonImgs.toggle();
					},
					success: function(ret) {
						buttonImgs.toggle();
						
						search = search.replaceWith(result);
						result.html(ret);

						$('#cp_reset_search').click(function() {
							result = result.replaceWith(search);

							$('#cp_search_form').submit(submit_handler);
							$('#cp_search_keywords').select();
							return false;
						});
					},
					dataType: 'html'
				});

				return false;
			}

			$('#cp_search_form').submit(submit_handler);

			$.getJSON('".str_replace('&amp;', '&', BASE)."&C=homepage&M=recently_edited', function(res) {
				$.each(res, function(key, val) {
					$('<li><a href=\''+val+'\'>'+key+'</a></li>').prependTo('#quickLinks ul');
				});
			});

			var site_chooser = $('#msm_site_list');

			// Match the container style
			var padding = $('#activeSite').css('paddingLeft');
			var color = $('#activeSite').css('backgroundColor');
			var offset = $('#activeSite').offset();

			site_chooser.css({
				'position': 'absolute',
				'padding': '0 '+ padding,
				'backgroundColor': color,
				'left': offset.left
			});

			$('#activeSite').bind('mouseleave', function() {
				site_chooser.slideUp('fast');
			});

			if (document.getElementById('cp_search_keywords'))
			{
				if ((parseInt(navigator.productSub)>=20020000)&&(navigator.vendor.indexOf('Apple Computer')!=-1))
				{
					searchField = document.getElementById('cp_search_keywords');
					searchField.setAttribute('type', 'search');
					searchField.setAttribute('autosave', 'ee_cp_search');
					searchField.setAttribute('results', '10');
					searchField.setAttribute('placeholder', 'Search');
				}
			}

			$('a[rel=\'external\']').click(function() {
				window.open($(this).attr('href'));
				return false;
			});
			
			var header_slidedown_active = false;

			// Wrapped to smooth out the animation
			$('#brandingInfo').wrap('<div id=\'brandingInfoWrapper\' style=\'display: none;\'></div>').show();

			$('#branding').hover(function() {
				$(this).find('img').hide();
				$(this).find('img.hover').show();
			}, function() {
				if ( ! header_slidedown_active) {
					$(this).find('img').show();
					$(this).find('img.hover').hide();
				}
			});

			$('#branding').click(function() {
				$('#brandingInfoWrapper').slideToggle('fast');
				header_slidedown_active = ( ! header_slidedown_active);
				return false;
			});
		");

		$this->javascript->click('#msm_switch_site',"
			site_chooser.show();
		");

		$this->javascript->output('
			if (EE.CP_SIDEBAR_STATE == "off") {
				$("#activeSite").siblings(":not(#activeSite, #revealSidebarLink)").hide();
				$("#mainContent").css("width", "100%");
				$("#revealSidebarLink").show();
				$("#hideSidebarLink").hide();
			}
		');

		// Logout button confirmation
		$this->javascript->output('
			$("<div id=\"logOutConfirm\">'.$this->lang->line("logout_confirm").'</div>").dialog({
				autoOpen: false,
				resizable: false,
				modal: true,
				title: "'.$this->lang->line('logout').'",
				position: "center",
				minHeight: "0px", // fix display bug, where the height of the dialog is too big
				buttons: {
					Cancel: function() {
						$(this).dialog("close");
					},
					"'.$this->lang->line('logout').'": function() {
						location="'.str_replace('&amp;', '&', BASE.AMP.'C=login'.AMP).'M=logout";
					}
				}
			});

			$("#logOut a.logOutButton").click(function(){
				$("#logOutConfirm").dialog("open");
				$(".ui-dialog-buttonpane button:eq(2)").focus(); //focus on Log-out so pressing return logs out
				return false;
			});
		');

		$this->javascript->output('
			// set up the close button
			$(".TB_closeWindowButton").click(function() {
				tb_remove();
				return false;
			});
			
			var notices = $(".notice");
			
			if (notices.length > 0) {
				notices = notices.slice(0, 1);
				
				if (EE.flashdata.message == notices.html()) {
					notices.remove();
					$.ee_notice(EE.flashdata.message);
				}
			}
		');

		// Clickstats
		$xid = (defined('XID_SECURE_HASH')) ? XID_SECURE_HASH : "";

		$this->javascript->output('
			var retrieving_clicks = false; // used to track if abort() is needed
			// if you are viewing source, then this next line might seem weird, but there is some PHP things going on there, I promise
			var user_type = ("'.$this->config->item('user_type').'" != "") ? "'.$this->config->item('user_type').'" : 0;

		    $(this).bind("mousedown.clickmap", function(evt) {
				var sub_nav = 0;

				$("#navigationTabs .parent ul").each(function(){
					if ($(this).css("left") == "0px")
					{
						sub_nav++;
					}
				});

				$.ajax({
					url: "'.str_replace('&amp;', '&', BASE).'&C=clickstats&M=save_click",
					type: "POST",
					data: "XID='.$xid.'&class="+EE.router_class+"&method="+EE.router_method+"&x="+evt.pageX+"&y="+evt.pageY+"&sub_nav="+sub_nav+"&user_type="+user_type,
				});
			});

			function show_clickstats()
			{
				$("<div id=\"clickstatLoading\"></div>").appendTo("body");

				retrieving_clicks = $.ajax({
					url: "'.str_replace('&amp;', '&', BASE).'&C=clickstats&M=show_clicks",
					type: "POST",
					data: "XID='.$xid.'&class="+EE.router_class+"&method="+EE.router_method,
					success: function (htmlContentFromServer) {
						$("#clickstatLoading").remove();
						$("<div id=\"clickstatOverlay\"></div>").appendTo("body");
						$(htmlContentFromServer).appendTo("#clickstatOverlay");
					}
				});
			}

			$(window).keyup(function(e)
			{
				if (e.ctrlKey)
				{
					if ($("#clickstatOverlay").length == 1 || $("#clickstatLoading").length == 1)
					{
						// anything that was there, remove() it
						$("#clickstatLoading").remove();
						$("#clickstatOverlay").remove();

						// this should always fire if we are here, but lets ensure there is 
						// actually a request underway anyhow.
						if (retrieving_clicks != false)
						{
							retrieving_clicks.abort();
						}
					}
					else
					{
						// anything there? remove() it
						$("#clickstatLoading").remove();
						$("#clickstatOverlay").remove();

						if (e.which == 83) // s key
						{
							show_clickstats();
						}
					}
				}
			});

		');
		// End Clickstats

		$this->javascript->compile('script_head', FALSE);

		$this->load->view('_shared/javascript.php');
	}

	/**
	 * Spellcheck iFrame
	 *
	 * Used by the Spellcheck crappola
	 *
	 * @access	public
	 * @return	void
	 */
	function spellcheck_iframe()
	{
		$this->output->enable_profiler(FALSE);
		
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT; 
		}

		return EE_Spellcheck::iframe();
	}

	// --------------------------------------------------------------------

	/**
	 * Spellcheck
	 *
	 * Used by the Spellcheck crappola
	 *
	 * @access	public
	 * @return	void
	 */
	function spellcheck()
	{
		$this->output->enable_profiler(FALSE);

		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck'.EXT; 
		}

		return EE_Spellcheck::check();
	}

	// --------------------------------------------------------------------

	/**
	 * Load
	 *
	 * Sends jQuery files to the browser
	 *
	 * @access	public
	 * @return	type
	 */
	function load()
	{
		$this->output->enable_profiler(FALSE);

		// trying to load a specific js file?
		$loadfile = $this->input->get_post('file');

		if ($loadfile == '')
		{
			if (($plugin = $this->input->get_post('plugin')) !== FALSE)
			{
				$file = PATH_JQUERY.'plugins/'.$plugin.'.js';
			}
			elseif (($ui = $this->input->get_post('ui')) !== FALSE)
			{
				$file = PATH_JQUERY.'ui/ui.'.$ui.'.js';
			}
			elseif (($effect = $this->input->get_post('effect')) !== FALSE)
			{
				$file = PATH_JQUERY.'ui/effect.'.$effect.'.js';
			}
			else
			{
				$file = PATH_JQUERY.'jquery.js';
			}
		}
		else
		{
			if ($this->config->item('use_compressed_js') == 'n')
			{
				$file = APPPATH.'javascript/src/'.$loadfile.'.js';
			}
			else
			{
				$file = APPPATH.'javascript/compressed/'.$loadfile.'.js';
			}
		}

		if ( ! file_exists($file))
		{

			if ($this->config->item('debug') >= 1)
			{
				$this->output->fatal_error($this->lang->line('missing_jquery_file'));
			}
			else
			{
				return FALSE;
			}

		}

		// Can't do any of this if we're not allowed
		// to send any headers

		if ($this->config->item('send_headers') == 'y')
		{
			$max_age		= 172800;
			$modified		= filemtime($file);
			$modified_since	= $this->input->server('HTTP_IF_MODIFIED_SINCE');

			// Remove anything after the semicolon

			if ($pos = strrpos($modified_since, ';') !== FALSE)
			{
				$modified_since = substr($modified_since, 0, $pos);
			}
			
			// Send a custom ETag to maintain a useful cache in
			// load-balanced environments
			
			header("ETag: ".md5($modified));
			
			// If the file is in the client cache, we'll
			// send a 304 and be done with it.

			if ($modified_since && (strtotime($modified_since) == $modified))
			{
				$this->output->set_status_header(304);
				exit;
			}

			// All times GMT
			$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
			$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

			$this->output->set_status_header(200);
			@header("Cache-Control: max-age={$max_age}, must-revalidate");
			@header('Last-Modified: '.$modified);
			@header('Expires: '.$expires);
		}

		// Grab the file, content length and serve
		// it up with the proper content type!

		$contents = file_get_contents($file);

		if ($this->config->item('send_headers') == 'y')
		{
			@header('Content-Length: '.strlen($contents));
		}

		header("Content-type: text/javascript");
		exit($contents);
	}

}

/* End of file javascript.php */
/* Location: ./system/expressionengine/controllers/cp/javascript.php */