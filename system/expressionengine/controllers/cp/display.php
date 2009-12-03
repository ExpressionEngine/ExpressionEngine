<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


// DEPRECATED - OLD VERSION

class Display {
 
	var $publish_nav	= 'hover';  // The PUBLISH tab drop down menu behavior. Either 'click' or 'hover'
	var $sites_nav		= 'hover';  // The PUBLISH tab drop down menu behavior. Either 'click' or 'hover'
	var $title	  	= '';	// Page title
	var $body			= '';	// Main content area
	var $crumb	  	= '';	// Breadcrumb.
	var $rcrumb	 	= '';	// Right side breadcrumb
	var $crumbline  	= FALSE;  // Assigns whether to show the line below the breadcrumb
	var $show_crumb 	= TRUE;  // Assigns whether to show the breadcrumb
	var $crumb_ov		= FALSE; // Crumb Override. Will prevent the "M" variable from getting auto-linked
	var $refresh		= FALSE; // If set to a URL, the header will contain a <meta> refresh
	var $ref_rate		= 0;	 // Rate of refresh
	var	$url_append		= '';	 // This variable lets us globally append something onto URLs
	var	$body_props		= '';	// Code that can be addded the the <body> tag
	var	$initial_body	= '';	// We can manually add things just after the <body> tag.
	var $extra_css		= '';	// Additional CSS that we can fetch from a different file.  It gets added to the main CSS request.
	var $manual_css		= '';	// Additional CSS that we can generate manually.  It gets added to the main CSS request.
	var $extra_header	= '';	// Additional headers we can add manually
	var $rcrumb_css		= 'breadcrumbRight';	 // The default CSS used in the right breadcrumb
	var $padding_tabs	= 'clear';	// on/off/clear  -  The navigation tabs have an extra cell on the left and right side to provide padding.  This determis how it should be displayed.  It interacts with this variable, which is placed in the CSS file:  {padding_tabs ="clear"}
	var $empty_menu		= FALSE;	// Is the Publish channel menu empty?
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	
	function Display()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

  		if ( ! defined('AMP')) define('AMP', '&amp;');
		if ( ! defined('BR'))  define('BR',  '<br />');
		if ( ! defined('NL'))  define('NL',  "\n");
		if ( ! defined('NBS')) define('NBS', "&nbsp;");	
		
		$this->sites_nav	= (in_array($this->EE->config->item('sites_tab_behavior'), array('click', 'hover', 'none'))) ? $this->EE->config->item('sites_tab_behavior') : $this->sites_nav;
		$this->publish_nav	= (in_array($this->EE->config->item('publish_tab_behavior'), array('click', 'hover', 'none'))) ? $this->EE->config->item('publish_tab_behavior') : $this->publish_nav;
	}

	
  
	/** -------------------------------------
	/**  Set return data
	/** -------------------------------------*/
  
	function set_return_data($title = '', $body = '', $crumb = '',  $rcrumb = '')
	{
		$this->title  = $title;
		$this->body	= $body;		
		$this->crumb  = $crumb;
		$this->rcrumb = $rcrumb;
	}  

 
 
	/** -------------------------------------
	/**  Group Access Verification
	/** -------------------------------------*/
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
			return FALSE;
		else
			return TRUE;
	}

  

	/** -------------------------------------
	/**  Control panel
	/** -------------------------------------*/
	function show_full_control_panel()
	{
		// -------------------------------------------
		// 'show_full_control_panel_start' hook.
		//  - Full Control over CP
		//  - Modify any EE->dsp class variable (JS, headers, etc.)
		//  - Override any EE->dsp method and use their own
		//
			$edata = $this->EE->extensions->call('show_full_control_panel_start');
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------
					
		$out =	$this->html_header()
				.$this->page_header()
				.$this->page_navigation()
				.$this->breadcrumb()
				.$this->content()
				.$this->content_close()
				.$this->copyright()
				.$this->html_footer();
				
		$out = $this->EE->functions->insert_action_ids($out);
				
		// -------------------------------------------
		// 'show_full_control_panel_end' hook.
		//  - Rewrite CP's HTML
		//	- Find/Replace Stuff, etc.
		//
			if ($this->EE->extensions->active_hook('show_full_control_panel_end') === TRUE)
			{
				$out = $this->EE->extensions->call('show_full_control_panel_end', $out);
				if ($this->EE->extensions->end_script === TRUE) return;
			}
		//
		// -------------------------------------------
					
		$this->EE->output->build_queue($out);
	}




	/** -------------------------------------
	/**  Show restricted version of CP
	/** -------------------------------------*/
	function show_restricted_control_panel()
	{
		$r = $this->html_header();
		
		// We treat the bookmarklet as a special case
		// and show the navigation links in the top right
		// side of the page
		
		if ($this->EE->input->get_post('BK') AND $this->EE->session->userdata['admin_sess'] == 1)
		{
			$r .= $this->page_header(0);
		}
		else
		{
			$r .= $this->simple_header('helpLinksLeft');
		}
		
		$r .= $this->content(TRUE);
		$r .= $this->content_close();
		$r .= $this->html_footer();
		
		$r = $this->EE->functions->insert_action_ids($r);
	
		$this->EE->output->set_output($r);
	}



	/** -------------------------------------
	/**  Show "login" version of CP
	/** -------------------------------------*/
	function show_login_control_panel()
	{
		$this->secure_hash();
		
		$r  = $this->html_header()
		.$this->simple_header()
		.$this->body
		.$this->copyright()
		.$this->html_footer();
	
		$this->EE->output->set_output($r);
	}



	
	
	/** -------------------------------------
	/**  HTML Header
	/** -------------------------------------*/
	function html_header($title = '')
	{
		if ($title == '')
			$title = $this->title;
				
		$header =

		"<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n".
		"<html>\n".
		"<head>\n".
		"<title>$title | ".APP_NAME."</title>\n\n".		
		"<meta http-equiv='content-type' content='text/html; charset=".$this->EE->config->item('output_charset')."' >\n".
		"<meta http-equiv='expires' content='-1' >\n".
		"<meta http-equiv='expires' content='Mon, 01 Jan 1970 23:59:59 GMT' >\n".		
		"<meta http-equiv='pragma' content='no-cache' >\n";
		
		if ($this->refresh !== FALSE)
		{
			$header .= "<meta http-equiv=\"refresh\" content=\"".$this->ref_rate."; url=".$this->refresh."\" >\n";
		}
		
		$stylesheet = $this->fetch_stylesheet();
		
		$header .=
	 
		"<style type='text/css'>\n".
		$stylesheet."\n\n".
		$this->manual_css.
		"</style>\n\n".
		$this->_menu_js().
		$this->_global_javascript().
		$this->extra_header.
		"</head>\n\n".
		"<body{$this->body_props}>\n".
		$this->initial_body."\n";
		
		return $header;
	}

	
		
	
	/** -------------------------------------
	/**  Fetch CSS Stylesheet
	/** -------------------------------------*/
	function fetch_stylesheet()
	{
		$cp_theme = ( ! isset($this->EE->session->userdata['cp_theme']) OR $this->EE->session->userdata['cp_theme'] == '') ? $this->EE->config->item('cp_theme') : $this->EE->session->userdata['cp_theme']; 
		
		// HACK to allow a working CSS file to be fetched during 2.0 development
		$cp_theme = 'default';
		
		
		$path = ( ! is_dir('./cp_themes/')) ? PATH_CP_THEME : './cp_themes/';
		
			
		if ( ! $theme = $this->file_open($path.$cp_theme.'/'.$cp_theme.'.css'))
		{
			if ( ! $theme = $this->file_open($path.'default/default.css'))
			{
				return '';
			}
		}
		
		if ($this->extra_css != '')
		{
			if ($extra = $this->file_open($this->extra_css))
			{
				$theme .= NL.NL.$extra;
			}
		}
		
		// Set the value of the "padding tabs" based on
		// a variable (that might be) contained in the CSS file
		
		if (preg_match("/\{padding_tabs\s*=\s*['|\"](.+?)['|\"]\}/", $theme, $match))
		{
			$this->padding_tabs = $match['1'];
			$theme = str_replace($match['0'], '', $theme);
		}		
		
		// Remove comments and spaces from CSS file
		$theme = preg_replace("/\/\*.*?\*\//s", '', $theme);
		$theme = preg_replace("/\}\s+/s", "}\n", $theme);
		
		// Replace the {path:image_url} variable. 
		
		$img_path = $this->EE->config->slash_item('theme_folder_url').'cp_themes/'.$cp_theme.'/images/';		
		$theme = str_replace('{path:image_url}', $img_path, $theme);
		
		// Change CSS on the click so it works like the hover until they unclick?  
		
		$tab_behaviors = array(
								'publish_tab_selector'		=> ($this->EE->config->item('publish_tab_behavior') == 'hover') ? 'hover' : 'active',
								'publish_tab_display'		=> ($this->EE->config->item('publish_tab_behavior') == 'none') ? '' : 'display:block; visibility: visible;',
								'publish_tab_ul_display'	=> ($this->EE->config->item('publish_tab_behavior') == 'none') ? '' : 'display:none;',
								'sites_tab_selector'		=> ($this->EE->config->item('sites_tab_behavior') == 'hover') ? 'hover' : 'active',
								'sites_tab_display'			=> ($this->EE->config->item('sites_tab_behavior') == 'none') ? '' : 'display:block; visibility: visible;',
								'sites_tab_ul_display'		=> ($this->EE->config->item('sites_tab_behavior') == 'none') ? '' : 'display:none;'
							);
		
		foreach ($tab_behaviors as $key => $val)
		{
			$theme = str_replace(LD.$key.RD, $val, $theme);
		}

		return $theme;	
	}

	
	
	/** -------------------------------------
	/**  File Opener
	/** -------------------------------------*/
	function file_open($file)
	{
		if ( ! $fp = @fopen($file, FOPEN_READ))
		{
			return FALSE;
		}
			
		flock($fp, LOCK_SH);
		
		$f = '';
		
		if (filesize($file) > 0) 
		{
			$f = fread($fp, filesize($file)); 
		}

		flock($fp, LOCK_UN);
		fclose($fp); 
	
		return $f;
	}

	
	

	/** -------------------------------------
	/**  Page Header
	/** -------------------------------------*/
	function page_header($header = TRUE)
	{		
		$r = "<div id='topBar'>\n"
			 .$this->table('', '', '0', '100%')
			 .$this->tr()
			 .$this->td('helpLinks')
			 .$this->div('helpLinksLeft')
			 .$this->anchor($this->EE->functions->fetch_site_index().QUERY_MARKER.'URL=http://expressionengine.com/', APP_NAME.$this->nbs(2).'v '.APP_VER)
			 .$this->div_c()
			 .$this->td_c()
			 .$this->td('helpLinks');
		
		$r .= $this->anchor(BASE.AMP.'C=myaccount'.AMP.'M=quicklinks'.AMP.'id='.$this->EE->session->userdata('member_id'),
							"<img src='".PATH_CP_GBL_IMG."edit_quicklinks.png' border='0' width='16' height='16' style='vertical-align: bottom;' alt='".$this->EE->lang->line('edit_quicklinks')."' />")
							.$this->nbs(3);
		
		$r .= $this->fetch_quicklinks();	

		$doc_path = $this->EE->config->item('doc_url');
		
		if (substr($doc_path, -1) != '/')
		{
			$doc_path .= '/';
		}

		$r .= $this->anchor(BASE, $this->EE->lang->line('main_menu')).$this->nbs(3).'|'.$this->nbs(3)
			 ."<a href='".$doc_path."' target='_blank'>".$this->EE->lang->line('user_guide').'</a>'.$this->nbs(3).'|'.$this->nbs(3)
			 .$this->anchor(BASE.AMP.'C=login&M=logout', $this->EE->lang->line('logout')).$this->nbs(3).'|'.$this->nbs(3);
			 		
		$r .= $this->anchor(BASE.AMP.'C=myaccount'.AMP.'M=tab_manager'.$this->generate_quick_tab(), $this->EE->lang->line('new_tab'))
			 .$this->td_c()
			 .$this->tr_c()
			 .$this->table_c()
			 .$this->div_c();
			
		if ($header != 0)
			$r .= "<div id='header'></div>\n";

		return $r;
	}

  
 	/** -------------------------------------
	/**  Quck Tabs
	/** -------------------------------------*/
	function fetch_quicktabs()
	{
		$tabs = array();
			
		if ( ! isset($this->EE->session->userdata['quick_tabs']) OR $this->EE->session->userdata['quick_tabs'] == '')
		{
			return $tabs;
		}
		
		foreach (explode("\n", $this->EE->session->userdata['quick_tabs']) as $row)
		{				
			$x = explode('|', $row);
			
			$title = (isset($x['0'])) ? $x['0'] : '';
			$link  = (isset($x['1'])) ? $x['1'] : '';
			
			$tabs[] = array($title, $link);	
		}
			
		return $tabs;
	}  


	/** -------------------------------------
	/**  Create the "quick add" link
	/** -------------------------------------*/
	  
	function generate_quick_tab()
	{
		$link  = '';
		$linkt = '';
		if ($this->EE->input->get_post('M') != 'tab_manager' AND $this->EE->input->get_post('M') != '')
		{
			foreach ($_GET as $key => $val)
			{
				if ($key == 'S')
					continue;
			
				$link .= $key.'--'.$val.'/';
			}
			
			$link = substr($link, 0, -1);
		}
		
		// Does the link already exist as a tab?
		// If so, we'll make the link blank so that the
		// tab manager won't let the user create another tab.
		
		$show_link = TRUE;
				
		if (isset($this->EE->session->userdata['quick_tabs']) AND $this->EE->session->userdata['quick_tabs'] != '')
		{
			$newlink = '|'.str_replace('/', '&', str_replace('--', '=', $link)).'|';
			
			if (strpos($this->EE->session->userdata['quick_tabs'], $newlink))
			{
				$show_link = FALSE;
			}
		}
		
		// We do not normally allow semicolons in GET variables, so we protect it
		// in this rare instance.
		$tablink = ($link != '' AND $show_link == TRUE) ? AMP.'link='.$link.AMP.'linkt='.base64_encode($this->title) : '';
	
		return $tablink;
	}
	  
	/** -------------------------------------
	/**  Quicklinks
	/** -------------------------------------*/
	function fetch_quicklinks()
	{
		if ( ! isset($this->EE->session->userdata['quick_links']) OR $this->EE->session->userdata['quick_links'] == '')
		{
			return '';
		}
		
		$r = '';
				 
		foreach (explode("\n", $this->EE->session->userdata['quick_links']) as $row)
		{				
			$x = explode('|', $row);
			
			$title = (isset($x['0'])) ? $x['0'] : '';
			$link  = (isset($x['1'])) ? $x['1'] : '';
	
			$r .= $this->anchor($this->EE->functions->fetch_site_index().QUERY_MARKER.'URL='.$link, $this->html_attribute_prep($title), '', 1).$this->nbs(3).'|'.$this->nbs(3);
	
		}
			
		return $r;
	}  

	/** -------------------------------------
	/**  Simple version of the Header
	/** -------------------------------------*/
	function simple_header($class ='loginLogo')
	{
		return
				
		"<div id='topBar'>\n"
		.$this->table('', '', '0', '100%')
		.$this->table_qrow('helpLinks', $this->qdiv($class, $this->nbs(2).APP_NAME.$this->nbs(2).'v '.APP_VER))
		.$this->table_c()
		.$this->div_c()
		."<div id='simpleHeader'></div>\n";
	}




	/** -------------------------------------
	/**  Equalize Text
	/** -------------------------------------*/
	// This function lets us "equalize" the text length by adding non-breaking spaces
	// before/after each line so that they all match.  This enables the
	// navigation buttons to have the same length.  The function must be passed an 
	// associative array
	
	function equalize_text($text = array())
	{
		 $longest = 0;	
		
		 foreach ($text as $val)
		 {
			$val = strlen($val);
		 
			if ($val > $longest)
				$longest = $val;
		 }
				
		 foreach ($text as $key => $val)
		 {
			$i = $longest - strlen($val);
			
			$i = ceil($i/2);
							
			$val = $this->nbs($i).$val.$this->nbs($i);
					
			$text[$key] = $val;
		 }
	
		return $text;
	}

	

	/** -------------------------------------
	/**  Main control panel navigation
	/** -------------------------------------*/
	function page_navigation()
	{
		/* -------------------------------------------
		/* 'cp_display_page_navigation' hook.
		/*  - Take control of the Control Panel's top navigation
		/*  - Added 1.5.0
		*/
			$r = $this->EE->extensions->universal_call('cp_display_page_navigation', $this);
			if ($this->EE->extensions->end_script === TRUE) return $r;
		/*
		// -------------------------------------------*/
		
		
		$C = ($this->EE->input->get('class_override') == '') ? $this->EE->input->get_post('C') : $this->EE->input->get_post('class_override') ;	
		
		// HACK to allow the Edit tab to be highlighted when loading publish.php in 2.0
		if (($M = $this->EE->input->get_post('M')) !== FALSE)
		{
			if (strncmp($M, 'edit_', 4) == 0 OR strpos($M, '_edit_') !== FALSE)
			{
				$C = 'edit';
			}
		}
			
		// First we'll gather the navigation menu text in the selected language.
				
		$text = array(  'sites'			=> $this->EE->lang->line('sites'),
						'publish'	 	=> $this->EE->lang->line('publish'),
						'edit'			=> $this->EE->lang->line('edit'),
						'design'		=> $this->EE->lang->line('design'),
						'communicate'	=> $this->EE->lang->line('communicate'),
						'modules'		=> $this->EE->lang->line('modules'),
						'my_account'	=> $this->EE->lang->line('my_account'),
						'admin'			=> $this->EE->lang->line('admin')		
					  );
					  
		if ($this->EE->config->item('multiple_sites_enabled') !== 'y')
		{
			unset($text['sites']);
		}
		 
		// Fetch the custom tabs if there are any
		
		$quicktabs = $this->fetch_quicktabs();
		 
		// Set access flags
		
		$cells = array(
							's_lock' => 'can_access_sites',
							'p_lock' => 'can_access_publish',
							'e_lock' => 'can_access_edit',
							'd_lock' => 'can_access_design',
							'c_lock' => 'can_access_comm',
							'm_lock' => 'can_access_modules',
							'a_lock' => 'can_access_admin'
						);
		
		// Dynamically set the table width based on the number
		// of tabs that will be shown.
		
		$tab_total	= count($text) + count($quicktabs); // Total possible tabs
		$width_base	= floor(80/$tab_total); // Width of each tab		
		$width_pad	= 98;

		foreach ($cells as $key => $val)
		{
			if ($key == 's_lock' && $this->EE->config->item('multiple_sites_enabled') == 'y' && count($this->EE->session->userdata('assigned_sites')) > 0)
			{
				$$key = 0;
			}
			elseif ( ! $this->allowed_group($val))
			{
				$$key = 1;
				$width_pad -= $width_base;
				$tab_total--;
			}
			else
			{
				$$key = 0;
			}
		}
		 
		if ($tab_total < 6)
		{
			$width 		= ($tab_total <= 0) ? 0 : ceil($width_pad/$tab_total);
			$width_pad	= floor(100-$width_pad);
		}
		else
		{
			$width 		= ceil(96/$tab_total);
			$width_pad	= 0;
		}
				  
		/*
		
		Does a custom tab need to be highlighted?
		Since users can have custom tabs we need to highlight them when the page is
		accessed.  However, when we do, we need to prevent any of the default tabs
		from being highlighted.  Say, for example, that someone creates a tab pointing
		to the Photo Gallery.  When that tab is accessed it needs to be highlighted (obviously)
		but we don't want the MODULES tab to also be highlighted or it'll look funny.
		Since the Photo Gallery is within the MODULES tab it'll hightlight itself automatically.
		So... we'll use a variable called:  $highlight_override
		When set to TRUE, this variable turns off all default tabs.
		The following code blasts thorough the GET variables to see if we have
		a custom tab to show.  If we do, we'll highlight it, and turn off
		all the other tabs.
		*/
  
		$highlight_override = FALSE;
		
		$tabs = '';
		$tabct = 1;
		if (count($quicktabs) > 0)
		{
			foreach ($quicktabs as $val)
			{
				$gkey = '';
				$gval = '';
				if (strpos($val['1'], '&'))
				{
					$x = explode('&', $val['1']);
					
					$i = 1;
					foreach ($x as $v)
					{ 
						$z = explode('=', $v);

						if (isset($_GET[$z['0']]))
						{
							if ($_GET[$z['0']] != $z['1'])
							{
								$gkey = '';
								$gval = '';
								break;
							}
							elseif (count($x)+1 == count($_GET))
							{ 
								$gkey = $z['0'];
								$gval = $z['1'];
							}
						}
					}
				}
				elseif (strpos($val['1'], '='))
				{
					$z = explode('=', $v);
				
					if (isset($_GET[$z['0']]))
					{
						$gkey = $z['0'];
						$gval = $z['1']; 
					}				
				}
				
				$tab_nav_on = FALSE;
		
				if (isset($_GET[$gkey]) AND $_GET[$gkey] == $gval)
				{
					$highlight_override = TRUE;
					$tab_nav_on = TRUE;
				}
				
				$linktext = ( ! isset($text[$val['0']])) ? $val['0'] : $text[$val['0']];
				$linktext = $this->clean_tab_text($linktext);
				
				$tabid = 'tab'.$tabct;
				$tabct ++;
												
				if ($tab_nav_on == TRUE) 
				{
					$js = ' onclick="navjump(\''.BASE.AMP.$val['1'].'\');"';
					$tabs .= "<td class='navCell' width='".$width."%' ".$js.">";  				
					$div = $this->qdiv('cpNavOn', $linktext);
				}
				else
				{
					$js = ' onclick="navjump(\''.BASE.AMP.$val['1'].'\');"  onmouseover="navTabOn(\''.$tabid.'\');" onmouseout="navTabOff(\''.$tabid.'\');" ';
					$tabs .= "<td class='navCell' width='".$width."%' ".$js.">";  				
					$div = $this->div('cpNavOff', '', $tabid).$linktext.$this->div_c();
				}
						
				$tabs .= $this->anchor(BASE.AMP.$val['1'], $div);
				$tabs .= $this->td_c();				
			}
		} 
		
		
		$r = '';
		
		/** -------------------------------------
		/**  Create Navigation Tabs
		/** -------------------------------------*/
		
		// Define which nav item to show based on the group 
		// permission settings and render the finalized navigaion  
		
				 
		$r .= $this->table('', '0', '0', '100%')
			 .$this->tr();
		
		if ($this->padding_tabs != 'off')
		{
			$r .= $this->td('navCell');
				 
			if ($this->padding_tabs == 'clear')
			{
				$r .= $this->nbs();
			}
			else
			{
				$r .= $this->div('cpNavOff')
					 .$this->nbs()
					 .$this->div_c();
			}
		
			$r .= $this->td_c().NL.NL;
 		}
		
		/** -------------------------------------
		/**  Sites Tab
		/** -------------------------------------*/
		
		if ($s_lock == 0 && count($this->EE->session->userdata('assigned_sites')) > 0 && $this->EE->config->item('multiple_sites_enabled') == 'y')
		{
			if ($this->sites_nav == 'click' && count($this->EE->session->userdata['assigned_sites']) > 0)
			{
				$js = ' onclick="dropdownmenu(\'sitesdropmenu\');return false;"';
			}
			else
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=admin'.AMP.'M=site_admin'.AMP.'P=sites_list'.'\');"';
			}
			
			if ($C == 'sites' AND $highlight_override == FALSE) 
			{
				$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['sites']));
			}
			else
			{
				$div = $this->div('cpNavOff', '', 'sites').$this->clean_tab_text($text['sites']).$this->div_c();
			}
			
			$page = '';
			
			foreach ($_GET as $key => $val)
			{
				// Remove the Session and Update segments
				if ($key == 'S' OR $key == 'U' OR strlen($key) > 1 OR stristr($val, 'update_'))
				{
					continue;
				}
				
				$page .= $key.'='.$val.'|';
			}
			
			if ($page != '')
			{
				$page = AMP."page=".str_replace('=', '_', base64_encode($page));
			}
			
			if (count($this->EE->session->userdata['assigned_sites']) > 0)
			{
				$div .= '<ul id="sitesdropmenu">';
				
				foreach($this->EE->session->userdata['assigned_sites'] as $site_id => $site_label)
				{
					$div .= "<li class='sitesdropmenuinner'><a href='".BASE.AMP."C=sites".AMP."site_id=".$site_id.$page."' title='".$this->html_attribute_prep($site_label)."' onclick='location.href=this.href;'>".$this->html_attribute_prep($site_label)."</a></li>";
				}
				
				if ($this->allowed_group('can_admin_sites'))
				{
					$div .= "<li class='publishdropmenuinner'><a href='".BASE.AMP."C=admin".AMP."M=site_admin".AMP."P=sites_list' title='".$this->EE->lang->line('edit_sites')."' onclick='location.href=this.href;'><em>&#187;&nbsp;".$this->EE->lang->line('edit_sites')."</em></a></li>";
				}
				
				$div .= "</ul>";
			}
			
			$r .= "<td class='navCell' width='".$width."%' ".$js.">";
			$r .= $this->anchor(BASE.AMP.'C=sites', $div);
			$r .= $this->td_c().NL.NL;
			
			$r .= $this->td('navCell');
				 
			if ($this->padding_tabs == 'clear')
			{
				$r .= $this->nbs();
			}
			else
			{
				$r .= $this->div('cpNavOff')
					 .$this->nbs()
					 .$this->div_c();
			}
		
			$r .= $this->td_c().NL.NL;
		}
		
		/** -------------------------------------
		/**  Publish Tab
		/** -------------------------------------*/
		
		// Define which nav item to show based on the group 
		// permission settings and render the finalized navigaion
			
		if ($p_lock == 0)
		{								
			if ($this->publish_nav == 'click' && count($this->EE->session->userdata['assigned_channels']) > 0)
			{
				$js = ' onclick="dropdownmenu(\'publishdropmenu\');return false;"';
			}
			else
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=publish'.'\');"';
			}
			
			$r .= "<td class='navCell' width='".$width."%' ".$js.">";
			
			if ($C == 'publish' AND $highlight_override == FALSE) 
			{
				$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['publish']));
			}
			else
			{
				$div = $this->div('cpNavOff', '', 'publish').$this->clean_tab_text($text['publish']).$this->div_c();
			}
			
			if (count($this->EE->session->userdata['assigned_channels']) > 0)
			{
				$div .= '<ul id="publishdropmenu">';
				
				foreach($this->EE->session->userdata['assigned_channels'] as $channel_id => $channel_label)
				{
					$div .= "<li class='publishdropmenuinner'><a href='".BASE.AMP."C=publish".AMP."M=entry_form".AMP."channel_id=".$channel_id."' title='".$this->html_attribute_prep($channel_label)."' onclick='location.href=this.href;'>".$this->html_attribute_prep($channel_label)."</a></li>";
				}
				
				if ($this->allowed_group('can_admin_channels'))
				{
					$div .= "<li class='publishdropmenuinner'><a href='".BASE.AMP."C=admin".AMP."M=channel_admin".AMP."P=channel_list' title='".$this->EE->lang->line('edit_channels')."' onclick='location.href=this.href;'><em>&#187;&nbsp;".$this->EE->lang->line('edit_channels')."</em></a></li>";
				}
				
				$div .= "</ul>";
			}
			
			
			$r .= $this->anchor(BASE.AMP.'C=publish', $div);
			$r .= $this->td_c().NL.NL;
		}
		
		/** -------------------------------------
		/**  Edit Tab
		/** -------------------------------------*/
			
		if ($e_lock == 0)
		{			  	
			if ($C == 'edit' AND $highlight_override == FALSE) 
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=publish'.AMP.'M=edit_entries\');"'; 
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['edit']));
			}
			else
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=publish'.AMP.'M=edit_entries\');" onmouseover="navTabOn(\'edit\');" onmouseout="navTabOff(\'edit\');" ';				
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->div('cpNavOff', '', 'edit').$this->clean_tab_text($text['edit']).$this->div_c();
			}
						
			$r .= $this->anchor(BASE.AMP.'C=publish'.AMP.'M=edit_entries', $div);
			$r .= $this->td_c().NL.NL;
		}
		
		
		/** -------------------------------------
		/**  Custom Tabs
		/** -------------------------------------*/
		
		$r .= $tabs;				
			
		if ($d_lock == 0)
		{								
			if ($C == 'templates' AND $highlight_override == FALSE) 
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=templates'.'\');"';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['design']));
			}
			else
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=templates'.'\');" onmouseover="navTabOn(\'design\');" onmouseout="navTabOff(\'design\');" ';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->div('cpNavOff', '', 'design').$this->clean_tab_text($text['design']).$this->div_c();
			}
						
			$r .= $this->anchor(BASE.AMP.'C=templates', $div);
			$r .= $this->td_c();
		}
		
		if ($c_lock == 0)
		{								
			if ($C == 'communicate' AND $highlight_override == FALSE) 
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=communicate'.'\');"';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['communicate']));
			}
			else
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=communicate'.'\');" onmouseover="navTabOn(\'communicate\');" onmouseout="navTabOff(\'communicate\');" ';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->div('cpNavOff', '', 'communicate').$this->clean_tab_text($text['communicate']).$this->div_c();
			}
						
			$r .= $this->anchor(BASE.AMP.'C=communicate', $div);
			$r .= $this->td_c();
		}
		
		if ($m_lock == 0)
		{						  	
			if ($C == 'modules' AND $highlight_override == FALSE) 
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=modules'.'\');"';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['modules']));
			}
			else
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=modules'.'\');" onmouseover="navTabOn(\'modules\');" onmouseout="navTabOff(\'modules\');" ';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->div('cpNavOff', '', 'modules').$this->clean_tab_text($text['modules']).$this->div_c();
			}
						
			$r .= $this->anchor(BASE.AMP.'C=modules', $div);
			$r .= $this->td_c();
		}
		
		
		// We only want the "MY ACCOUNT" tab highlighted if
		// the profile being viewed belongs to the logged in user
		
		$tab = $this->div('cpNavOff');

		if ($C == 'myaccount')
		{
			$id = ( ! $this->EE->input->get_post('id')) ? $this->EE->session->userdata('member_id') : $this->EE->input->get_post('id');
						
			if ($id != $this->EE->session->userdata('member_id'))
			{
				$tab = $this->div('cpNavOff');
			}
			else
			{
				if ($highlight_override == FALSE)
					$tab = $this->div('cpNavOn');
			}
		}
				
		if ($C == 'myaccount' AND $highlight_override == FALSE) 
		{
			$js = ' onclick="navjump(\''.BASE.AMP.'C=myaccount'.'\');"';
			$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
			$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['my_account']));
		}
		else
		{
			$js = ' onclick="navjump(\''.BASE.AMP.'C=myaccount'.'\');" onmouseover="navTabOn(\'my_account\');" onmouseout="navTabOff(\'my_account\');" ';
			$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
			$div = $this->div('cpNavOff', '', 'my_account').$this->clean_tab_text($text['my_account']).$this->div_c();
		}
					
		$r .= $this->anchor(BASE.AMP.'C=myaccount', $div);
		$r .= $this->td_c();
		
			
		if ($a_lock == 0)
		{ 					
			if ($C == 'admin' AND $highlight_override == FALSE) 
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=admin'.'\');"';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->qdiv('cpNavOn', $this->clean_tab_text($text['admin']));
			}
			else
			{
				$js = ' onclick="navjump(\''.BASE.AMP.'C=admin'.'\');" onmouseover="navTabOn(\'admin\');" onmouseout="navTabOff(\'admin\');" ';
				$r .= "<td class='navCell' width='".$width."%' ".$js.">";  
				$div = $this->div('cpNavOff', '', 'admin').$this->clean_tab_text($text['admin']).$this->div_c();
			}
						
			$r .= $this->anchor(BASE.AMP.'C=admin', $div);
			$r .= $this->td_c();
		}
		
		if ($this->padding_tabs != 'off')
		{
			$r .= $this->td('navCell', (($width_pad <= 2) ? '': $width_pad.'%'));
				
			if ($this->padding_tabs == 'clear')
			{
				$r .= $this->nbs();
			}
			else
			{
				$r .= $this->div('cpNavOff')
					 .$this->nbs()
					 .$this->div_c();
			}
		
			$r .= $this->td_c();
 		}
		
		$r .= $this->tr_c().
			  $this->table_c().
			  $this->nl(2);
			
		return $r;
	}



	/** -------------------------------------
	/**  This keeps the quick tab text OK
	/** -------------------------------------*/
	function clean_tab_text($str = '')
	{
		if ($str == '')
			return '';
			
		$str = str_replace(' ', NBS, $str);
		$str = str_replace('"', '&quot;', $str);
		$str = str_replace("'", "&#39;", $str);
		
		return $str;
	}

	

	/** -------------------------------------
	/**  Content
	/** -------------------------------------*/
	function content($padding = FALSE)
	{ 
		$this->secure_hash();
		
		if ($padding === TRUE)
		{
			$this->body = $this->qdiv('itemWrapperTop', $this->body);
		}
	
		if ($this->crumbline == FALSE)
		{	
			return NL."<div id='contentNB'>".$this->nl(2).$this->body.$this->nl(2);
		}
		else
		{
 			return NL."<div id='content'>".$this->nl(2).$this->body.$this->nl(2);
 		}
	}

	
	
	/** -------------------------------------
	/**  Secure Hash
	/** -------------------------------------*/
	function secure_hash($str = '')
	{
		$check = ($str != '') ? $str : $this->body;
		
		if ($this->EE->config->item('secure_forms') == 'y' && preg_match_all("/<form.*?>/", $check, $matches))  // <?php  fixex BBEdit display bug
		{
			$sql = "INSERT INTO exp_security_hashes (date, ip_address, hash) VALUES ";
			
			for($i=0, $s=count($matches['0']); $i < $s; ++$i)
			{
				$hash = $this->EE->functions->random('encrypt');
				$sql .= "(UNIX_TIMESTAMP(), '".$this->EE->input->ip_address()."', '".$hash."'),";
				
				$check = str_replace($matches['0'][$i], $matches['0'][$i].NL.$this->input_hidden('XID', $hash), $check);
			}
			
			$check = str_replace('{XID_SECURE_HASH}', $hash, $check);
			
			$this->EE->db->query(substr($sql,0,-1));
		}
		
		if ($str != '')
		{
			return $check;
		}
		else
		{
			$this->body = $check;
		}
	}



	/** -------------------------------------
	/**  Crumb Builder
	/** -------------------------------------*/
	
	// This function lets us build crumbs.  It can receive either a string or an array.
	// If you pass it an array  the key must be the name of the crumb and the value 
	// must be the URL where the crumb points.  If the value is blank only the text will appear.
	// EXAMPLE:
	/*
		$crumbs = array(	
							'Forum'			=> BASE.AMP.'C=modules'.AMP.'M=forum',
							'Forum Manager	=> BASE.AMP.'C=modules'.AMP.'M=forum'.AMP.'P=forum_manager',
							'Categories'	=> ''
						);
	
	
		$this->EE->dsp->crumb = $this->EE->dsp->build_crumb($crumbs);
		
		The above would produce:
		
		<a href="bla...">Forum</a> > <a href="bla..">Forum Manager</a> > Cateogories
	*/
	function build_crumb($crumbs = '')
	{ 
		if ($crumbs == '')
		{
			return '';
		}
	
		if ( ! is_array($crumbs))
		{
			return $this->crumb_item($crumbs);
		}
			
		if (count($crumbs) == 0)
			return '';
		
		$str = '';
		
		foreach ($crumbs as $key => $val)
		{
			if ($val == '')
			{
				$str .= $this->crumb_item($key);
			}
			else
			{
				$str .= $this->crumb_item($this->anchor($val, $key));
			}
		}	

		return $str;
	}

	
	 
	/** -------------------------------------
	/**  Breadcrumb
	/** -------------------------------------*/
	function breadcrumb()
	{
		if ($this->show_crumb == FALSE)
		{	
			return;
		}
		
		if ($this->EE->config->item('multiple_sites_enabled') == 'y')
		{
			if ($C = $this->EE->input->get_post('C'))
			{
				$link = $this->anchor(BASE, $this->html_attribute_prep($this->EE->config->item('site_name')));
			}
			else
			{
				$link = $this->anchor(BASE, $this->html_attribute_prep($this->EE->config->item('site_name'))).$this->nbs(2)."&#8250;".$this->nbs(2).$this->EE->lang->line('main_menu');
			}
		}
		else
		{
			$C = $this->EE->input->get_post('C');
			
			$link = $this->anchor(BASE, $this->EE->lang->line('main_menu'));
		}
						
		if ($this->EE->input->get('class_override') != '')
		{
			$C = $this->EE->input->get('class_override') ;
		}
	
		
		// If the "M" variable exists in the GET query string, turn 
		// the variable into the next segment of the breadcrumb
		
		if ($this->EE->input->get_post('M') AND $this->crumb_ov == FALSE)
		{			
			// The $special variable let's us add additional data to the query string
			// There are a few occasions where this is necessary
			
			$special = '';
						
			if ($this->EE->input->post('channel_id'))
			{
				$special = AMP.'channel_id='.$this->EE->input->get_post('channel_id');
			}

			// this is a bug fix so that 2.0 nav will work right.
			// In 2.0 we no longer use C=edit in the URI so things are a little wonky
			if (substr($this->EE->input->get('M'), 0, 5) == 'edit_' OR $this->EE->input->get('entry_id') != FALSE)
			{
				$name = $this->EE->lang->line('edit');
				$special = AMP.'M=edit_entries';
			}	
			else
			{
				// Build the link
				$name = ($C == 'templates') ? $this->EE->lang->line('design') : $this->EE->lang->line($C);
			}
						
			if (empty($name)) 
			{	
				$name = ucfirst($C);
			}
			
			if ($C == 'myaccount')
			{
				if ($id = $this->EE->input->get_post('id'))
				{
					if ($id != $this->EE->session->userdata('member_id'))
					{
						$name = $this->EE->lang->line('user_account');
						
						$special = AMP.'id='.$id;
					}
					else
					{
						$name = $this->EE->lang->line('my_account');
					}
				}
			}
		 
			$link .= $this->nbs(2)."&#8250;".$this->nbs(2).$this->anchor(BASE.AMP.'C='.$C.$special, $name);		
		}
		
		// $this->crumb indicates the page being currently viewed.
		// It does not need to be a link.
		
		if ($this->crumb != '')
		{
			$link .= $this->nbs(2)."&#8250;".$this->nbs(2).$this->crumb;
		}

		// This is the right side of the breadcrumb area.

		$data = ($this->rcrumb == '') ? "&nbsp;" : $this->rcrumb;
			
		if ($data == 'OFF')
		{
			$link = '&nbsp;';
			$data = '&nbsp;';
		}
		
		// Define the breadcrump CSS.  On all but the PUBLISH page we use the
		// version of the breadcrumb that has a bottom border
		
		if ($this->crumbline == TRUE)		
		{
			$ret = "<div id='breadcrumb'>";
		}
		else
		{
			$ret = "<div id='breadcrumbNoLine'>";
		}
						
		$ret .= $this->table('', '0', '0', '100%');
		$ret .= $this->tr();
		$ret .= $this->table_qcell('crumbPad', $this->span('crumblinks').$link.$this->span_c());
		$ret .= $this->table_qcell($this->rcrumb_css, $data, '270px', 'bottom', 'right');
		$ret .= $this->tr_c();
		$ret .= $this->table_c();
		$ret .= $this->div_c();
		
		return $ret;
	}


 
	/** ---------------------------------------
	/**  Right Side Crumb
	/** ---------------------------------------*/
 
	function right_crumb($title, $url = '', $extra = '', $pop = FALSE)
	{
		if ($title == '')
		{
			return;
		}
		
		$nj = '';
		if ($url != '')
		{
			if ($pop === FALSE)
			{
				$nj = ' onclick="navjump(\''.$url.'\');this.blur();" ';
			}
			else
			{
				$nj = " onclick=\"window.open('{$url}', '_blank');return false;\" ";
			}		
		}

		$js = $nj.$extra.' onmouseover="navCrumbOn();" onmouseout="navCrumbOff();" ';
		
		if ($url != '')
		{
			$this->rcrumb = $this->anchor($url, '<span class="crumblinksR" id="rcrumb" '.$js.'>'.$title.'</span>');
		}
		else
		{
			$this->rcrumb = $this->anchor('javascript:nullo();', '<span class="crumblinksR" id="rcrumb" '.$js.'>'.$title.'</span>');
		}
	}

	
 
	/** ---------------------------------------
	/**  Adds "breadcrum" formatting to an item
	/** ---------------------------------------*/
 
	function crumb_item($item)
	{
		return $this->nbs(2)."&#8250;".$this->nbs(2).$item;
	} 
 

	/** -------------------------------------
	/**  Required field indicator
	/** -------------------------------------*/
	function required($blurb = '')
	{
		if ($blurb == 1)
		{
			$blurb = "<span class='default'>".$this->nbs(2).$this->EE->lang->line('required_fields').'</span>';
		}
		elseif($blurb != '')
		{
			$blurb = "<span class='default'>".$this->nbs(2).$blurb.'</span>';
		}
	
		return "<span class='alert'>*</span>".$blurb.NL;
	}



	/** -------------------------------------
	/**  Content closing </div> tag
	/** -------------------------------------*/
	function content_close()
	{	
		return "</div>".NL;
	}




	/** -------------------------------------
	/**  Copyright
	/** -------------------------------------*/
	function copyright()
	{
		$logo = '<img src="'.PATH_CP_GBL_IMG.'ee_logo_sm.gif" border="0"  width="20" height="12" alt="ExpressionEngine" />';

		$core = '';
		$buyit = '';
		if ( ! file_exists(PATH_MOD.'member/mod.member'.EXT))
		{
			$core = ' Core';
			$buyit = 'Love EE Core? Consider '.$this->anchor($this->EE->functions->fetch_site_index().QUERY_MARKER.'URL=http://expressionengine.com/', 'buying').' a personal license!<br />';
		}

		return

		"<div class='copyright'>". $logo.$this->nl(2).$this->br().$this->nl().
		 $this->anchor($this->EE->functions->fetch_site_index().QUERY_MARKER.'URL=http://expressionengine.com/', APP_NAME.$core." ".APP_VER)." - &#169; ".$this->EE->lang->line('copyright')." 2003 - 2009 - EllisLab, Inc.".BR.NL.
		 $buyit.
		 str_replace("%x", "{elapsed_time}", $this->EE->lang->line('page_rendered')).$this->nbs(3).
		 str_replace("%x", $this->EE->db->query_count, $this->EE->lang->line('queries_executed')).$this->br().
		 $this->EE->lang->line('build').$this->nbs(2).APP_BUILD.$this->nl(2).
		"</div>".NL;
	}

		


	/** -------------------------------------
	/**  HTML Footer
	/** -------------------------------------*/
	function html_footer()
	{
		return NL.'</body>'.NL.'</html>';
	}



	/** -------------------------------------
	/**  Error Message
	/** -------------------------------------*/
	function error_message($message = "", $n = 1)
	{
		$this->title = $this->EE->lang->line('error');
		
		if (is_array($message))
		{
			$message = implode(BR, $message);
		}
		
		$this->crumbline = FALSE;
				
		$this->body = $this->qdiv('alertHeadingCenter', $this->EE->lang->line('error'))
				.$this->div('box')
				.$this->div('defaultCenter')
				.$this->qdiv('defaultBold', $message);
					
		if ($n != 0)
			$this->body .= BR.$this->nl(2)."<a href='javascript:history.go(-".$n.")' style='text-transform:uppercase;'>&#171; <b>".$this->EE->lang->line('back')."</b></a>";
			
		$this->body .= BR.BR.$this->div_c().$this->div_c();			
	}



	/** -------------------------------------
	/**  Unauthorized access message
	/** -------------------------------------*/
	function no_access_message($message = '')
	{
		$this->title = $this->EE->lang->line('unauthorized');
		
		$msg = ($message == '') ? $this->EE->lang->line('unauthorized_access') : $message;
		
		$this->body = $this->qdiv('highlight', BR.$msg);	
	}




	/** -------------------------------------
	/**  Global Javascript
	/** -------------------------------------*/
	function _global_javascript()
	{
		ob_start();
		?>
			<script type="text/javascript"> 
			<!--
			
			var browser = "Unknown";
			var version = "Unknown";
			var OS 		= "Unknown";
		
			var info = navigator.userAgent.toLowerCase();
							
			var browsers = new Array();
				browsers['safari']		= "Safari";
				browsers['omniweb']		= "OmniWeb";
				browsers['opera']		= "Opera"; 
				browsers['webtv']		= "WebTV"; 
				browsers['icab']		= "iCab";
				browsers['konqueror']	= "Konqueror";
				browsers['msie']		= "IE";
				browsers['mozilla']		= "Mozilla";
				
			for (b in browsers)
			{
				pos = info.indexOf(b) + 1;
				if (pos != false)
				{
					browser = browsers[b];
					version = info.charAt(pos + b.length);
					break;
				}	
			}
			
			var systems = new Array();
				systems['linux']	= "Linux";
				systems['x11'] 		= "Unix";
				systems['mac'] 		= "Mac";
				systems['win'] 		= "Win";

		
			for (s in systems)
			{
				pos = info.indexOf(s) + 1;
				if (pos != false)
				{
					OS = systems[s];
					break;
				}	
			}
			
			
			function navCrumbOn()
			{
				if (document.getElementById('rcrumb').className == 'crumblinksR')
				{
					document.getElementById('rcrumb').className = 'crumblinksRHover';
				}
			}
			
			function navCrumbOff()
			{
				if (document.getElementById('rcrumb').className == 'crumblinksRHover')
				{
					document.getElementById('rcrumb').className = 'crumblinksR';
				}
			}
	
			function navTabOn(link, idoff, idhover)
			{
				if ( ! idoff)
					idoff = 'cpNavOff';
					
				if ( ! idhover)
					idhover = 'cpNavHover';
			
				if (document.getElementById(link))
				{
					if (document.getElementById(link).className == idoff)
					{
						document.getElementById(link).className = idhover;
					}
				}
			}
		
			function navTabOff(link, idoff, idhover)
			{  
				if ( ! idoff)
					idoff = 'cpNavOff';
					
				if ( ! idhover)
					idhover = 'cpNavHover';		
			
				if (document.getElementById(link).className == idhover)
				{
					document.getElementById(link).className = idoff;
				}
			}
			
			function navjump(where, pop)
			{			
				if (browser != 'IE')
					return false;
			
				if (pop == 'true')
				{
					window.open(where, '_blank');
				}
				else
				{
					window.location=where;
				}
			}			
			//-->
			</script>
			
			<!--[if lt IE 7]>
			<script language="JavaScript">
			/*
			/* Fix for PNG alpha transparency for Internet Explorer
			/* Solution courtesy Bob Osola
			/* http://homepage.ntlworld.com/bobosola/index.htm
			*/
			function correctPNG() // correctly handle PNG transparency in Win IE 5.5 & 6.
			{
				var arVersion = navigator.appVersion.split("MSIE")
				var version = parseFloat(arVersion[1])
				if ((version >= 5.5) && (document.body.filters)) 
				{
				  for(var i=0; i<document.images.length; i++)
				  {
					 var img = document.images[i]
					 var imgName = img.src.toUpperCase()
					 if (imgName.substring(imgName.length-3, imgName.length) == "PNG")
					 {
						var imgID = (img.id) ? "id='" + img.id + "' " : ""
						var imgClass = (img.className) ? "class='" + img.className + "' " : ""
						var imgTitle = (img.title) ? "title='" + img.title + "' " : "title='" + img.alt + "' "
						var imgStyle = "display:inline-block;" + img.style.cssText 
						if (img.align == "left") imgStyle = "float:left;" + imgStyle
						if (img.align == "right") imgStyle = "float:right;" + imgStyle
						if (img.parentElement.href) imgStyle = "cursor:hand;" + imgStyle
						var strNewHTML = "<span " + imgID + imgClass + imgTitle
						+ " style=\"" + "width:" + img.width + "px; height:" + img.height + "px;" + imgStyle + ";"
						+ "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader"
						+ "(src=\'" + img.src + "\', sizingMethod='scale');\"></span>" 
						img.outerHTML = strNewHTML
						i = i-1
					 }
				  }
				}	
			}
			window.attachEvent("onload", correctPNG);
			</script>
			<![endif]-->
		<?php
		
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}

	

	/** -------------------------------------
	/**  Place-holder menu JS
	/** -------------------------------------*/
	function _menu_js()
	{
		ob_start();
		?>
			<script type="text/javascript"> 
			<!--
			var menu = new Array();
			function dropdownmenu(el)
			{
				if (document.getElementById(el).style.visibility == 'visible')
				{
					document.getElementById(el).style.display = 'none';
					document.getElementById(el).style.visibility = 'hidden';		
				}
				else
				{
					document.getElementById(el).style.display = 'block';
					document.getElementById(el).style.visibility = 'visible';
				}
			}
			
			function delayhidemenu(){
				return false;
			}
			
			//-->
			</script>
			
		<?php
		
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}



	/** -------------------------------------
	/**  Paginate 
	/** -------------------------------------*/
	function pager($base_url = '', $total_count = '', $per_page = '', $cur_page = '', $qstr_var = '')
	{
		// Instantiate the "paginate" class.
  
		if ( ! class_exists('Paginate'))
		{
			require APPPATH.'_to_be_replaced/lib.paginate'.EXT;
		}
		
		$PGR = new Paginate();
		
		$PGR->base_url	 = $base_url;
		$PGR->total_count  = $total_count;
		$PGR->per_page	 = $per_page;
		$PGR->cur_page	 = $cur_page;
		$PGR->qstr_var	 = $qstr_var;
		
		return $PGR->show_links();
	}



	/** -------------------------------------
	/**  Delete Confirmation Wrapper
	/** -------------------------------------*/
	// Creates a standardized confirmation message used whenever
	// something needs to be deleted.  The prototype for this form is:
	/*
		$r = $this->EE->dsp->delete_confirmation(
										array(
												'url'		=> 'C=modules'.AMP.'P=delete_module_confirm',
												'heading'	=> 'delete_module_heading',
												'message'	=> 'delete_module_message,
												'item'		=> $module_name,
												'extra'		=> '',
												'hidden'	=> array('module_id' => $module_id)
											)
										);	
	
	*/
	
	function delete_confirmation($data = array())
	{
		$vals = array('url', 'heading', 'message', 'item', 'hidden', 'extra');
		
		foreach ($vals as $val)
		{
			if ( ! isset($data[$val]))
			{
				$data[$val] = '';
			}
		}
		
		$r = $this->form_open(array('action' => $data['url']));
		
		if (is_array($data['hidden']))
		{
			foreach ($data['hidden'] as $key => $val)
			{
				$r .= $this->input_hidden($key, $val);
			}
		}
			
		$this->crumbline = FALSE;
		
		$r	.= 	 $this->qdiv('alertHeading', $this->EE->lang->line($data['heading']))
				.$this->div('box')
				.$this->qdiv('itemWrapper', '<b>'.$this->EE->lang->line($data['message']).'</b>')			
				.$this->qdiv('itemWrapper', $this->qdiv('highlight_alt', $data['item']));
		
		if ($data['extra'] != '')
		{
			$r .= $this->qdiv('itemWrapper', '<b>'.$this->EE->lang->line($data['extra']).'</b>');
		}
				
		$r .=	 $this->qdiv('itemWrapper', $this->qdiv('alert', $this->EE->lang->line('action_can_not_be_undone')))
				.$this->qdiv('itemWrapperTop', $this->input_submit($this->EE->lang->line('delete')))
				.$this->div_c()
				.$this->form_close();
	
		return $r;
	}

	

	/** -------------------------------------
	/**  Div
	/** -------------------------------------*/
	function div($style='default', $align = '', $id = '', $name = '', $extra='')
	{
		if ($align != '')
			$align = " align='{$align}'";
		if ($id != '')
			$id = " id='{$id}' ";
		if ($name != '')
			$name = " name='{$name}' ";
			
		$extra = ' '.trim($extra);
	
		return NL."<div class='{$style}'{$id}{$name}{$align}{$extra}>".NL;
	}



	/** -------------------------------------
	/**  Div close
	/** -------------------------------------*/
	function div_c()
	{
		return NL."</div>".NL;
	}



	/** -------------------------------------
	/**  Quick div
	/** -------------------------------------*/
	function qdiv($style='', $data = '', $id = '', $extra = '')
	{
		if ($style == '')
			$style = 'default';
		if ($id != '')
			$id = " id='{$id}' ";
			
		$extra = ' '.trim($extra);
	
		return NL."<div class='{$style}'{$id}{$extra}>".$data.'</div>'.NL;
	}




	/** -------------------------------------
	/**  Span
	/** -------------------------------------*/
	function span($style='default', $extra = '')
	{	
		if ($extra != '')
			$extra = ' '.$extra;
			
		return "<span class='{$style}'{$extra}>".NL;
	}



	/** -------------------------------------
	/**  Span close
	/** -------------------------------------*/
	function span_c($style='default')
	{
		return NL."</span>".NL;
	}



	/** -------------------------------------
	/**  Quick span
	/** -------------------------------------*/
	function qspan($style='', $data = '', $id = '', $extra = '')
	{
		if ($style == '')
			$style = 'default';
		if ($id != '')
			$id = " name = '{$id}' id='{$id}' ";
		if ($extra != '')
			$extra = ' '.$extra;	

		return NL."<span class='{$style}'{$id}{$extra}>".$data.'</span>'.NL;
	}



	/** -------------------------------------
	/**  Heading
	/** -------------------------------------*/
	function heading($data = '', $h = '1')
	{
		return NL."<h".$h.">".$data."</h".$h.">".NL;
	}



	/** -------------------------------------------
	/**  Anchor Tag
	/** -------------------------------------------*/
	
	function anchor($url, $name = "", $extra = '', $pop = FALSE)
	{
		if ($name == "" OR $url == "")
			return false;
			
		if ($pop != FALSE)
		{
			$pop = " target=\"_blank\"";
		}
		
		$url .= $this->url_append;
	
		return "<a href='{$url}' ".$extra.$pop.">$name</a>";
	}

	

	/** -------------------------------------------
	/**  Anchor - pop-up version
	/** -------------------------------------------*/
	
	function anchorpop($url, $name, $width='500', $height='480')
	{	
		return "<a href='javascript:nullo();' onclick=\"window.open('{$url}', '_blank', 'width={$width},height={$height},scrollbars=yes,status=yes,screenx=0,screeny=0,resizable=yes'); return false;\">$name</a>";
	}

	
	
	/** -------------------------------------------
	/**  Anchor - pop-up version - full page
	/** -------------------------------------------*/
	
	function pagepop($url, $name)
	{	
		return "<a href='#' onclick=\"window.open('{$url}', '_blank');return false;\">$name</a>";
	}


	
	/** -------------------------------------------
	/**  Mailto Tag
	/** -------------------------------------------*/
	
	function mailto($email, $name = "")
	{
		if ($name == "") $name = $email;

		return "<a href='mailto:{$email}'>$name</a>";
	}



	/** -------------------------------------------
	/**  <br /> Tags
	/** -------------------------------------------*/
	
	function br($num = 1)
	{
		return str_repeat("<br />\n", $num);
	}

	
	
	/** -------------------------------------------
	/**  "quick" <br /> tag with <div>
	/** -------------------------------------------*/
	
	function qbr($num = 1)
	{
		return NL.'<div>'.str_repeat("<br />\n", $num).'</div>'.NL;
	}



	/** -------------------------------------------
	/**  Item group
	/** -------------------------------------------*/
	
	function itemgroup($top = '', $bottom = '')
	{
		return $this->div('itemWrapper').
				$this->qdiv('itemTitle', $top).
				$bottom.
				$this->div_c();
	}



	/** -------------------------------------------
	/**  Newline characters
	/** -------------------------------------------*/
	
	function nl($num = 1)
	{
		return str_repeat("\n", $num);
	}


	
	/** -------------------------------------------
	/**  &nbsp; entity
	/** -------------------------------------------*/
	
	function nbs($num = 1)
	{
		return str_repeat("&nbsp;", $num);	
	}

	

	/** -------------------------------------------
	/**  Table open
	/** -------------------------------------------*/
		
	function table_open($props = array())
	{
		$str = '';
		
		foreach ($props as $key => $val)
		{
			if ($key == 'width')
			{
				$str .= " style='width:{$val};' ";
			}
			else
			{
				$str .= " {$key}='{$val}' ";
			}
		}
		
		$required = array('cellspacing' => '0', 'cellpadding' => '0', 'border' => '0');

		foreach ($required as $key => $val)
		{
			if ( ! isset($props[$key]))
			{
				$str .= " {$key}='{$val}' ";
			}
		}
		
		return "<table{$str}>".NL;
	}



	/** -------------------------------------------
	/**  Table Row
	/** -------------------------------------------*/
	
	function table_row($array = array())
	{
		$params		= '';
		$content	= '';		
		$end_row 	= FALSE; 
	
		$str = "<tr>".NL;
	
		foreach($array as $key => $val)
		{	
			if (is_array($val))
			{
				$params		= '';
				$content	= '';		
			
				foreach($val as $k => $v)
				{				
					if ($k == 'width')
					{
						$params .= " style='width:{$v};'";
					}
					else
					{
						if ($k == 'text')
						{
							$content = $v;
						}
						else
						{
							$params .= " {$k}='{$v}'";
						}
					}
				}

				$str .= "<td".$params.">";
				$str .= $content;
				$str .= "</td>".NL;				
			}
			else
			{ 
				$end_row = TRUE;
			
				if ($key == 'width')
				{
					$params .= " style='width:{$val};'";
				}
				else
				{
					if ($key == 'text')
					{
						$content .= $val;
					}
					else
					{
						$params .= " {$key}='{$val}'";
					}
				}
			}			
		}
		
		if ($end_row == TRUE)
		{
			$str .= "<td".$params.">";
			$str .= $content;
			$str .= "</td>".NL;				
		}
		
		$str .= "</tr>".NL;

		return $str;
	}



	/** -------------------------------------------
	/**  Table close
	/** -------------------------------------------*/
	
	function table_close($padding = FALSE)
	{
		$r = '</table>'.NL;
			
		if ($padding !== FALSE)
		{
			$r .= $this->qdiv('defaultSmall', $padding);
		}
	
		return $r;
	}



	/** -------------------------------------------
	/**  Form declaration - new version
	/** -------------------------------------------*/
	
	/*	EXAMPLE:
	
		The first parameter is an array containing the "action" and any other items that
		are desired in the form opening.  The second optional parameter is an array of hidden fields
	
		$r = $this->EE->dsp->form_open(	
								array(
										'action'	=> 'C=modules'.AMP.'M=forum', 
										'method'	=> 'post',
										'name'		=> 'entryform',
										'id'		=> 'entryform'
									 ),
								array(
										'member_id' => $mod_forum_id,
										'status'	=> $status
									)
							 );
	
		The above code will produce:
		
		<form action="C=modules&M=forum" method="post" name="entryform" id="entryform" />
		<input type="hidden" name="member_id" value="23" />
		<input type="hidden" name="status" value="open" />
		
		Notes:  
				The method in the first parameter is not required.  It ommited it'll be set to "post".
				
				If the first parameter does not contain an array it is assumed that it contains
				the "action" and will be treated as such.
	*/
	
	function form_open($data = '', $hidden = array())
	{
		if ( ! is_array($data))
		{
			$data = array('action' => $data);
		}
		
		if ( ! isset($data['action']))
		{
			$data['action'] = '';
		}
		
		if ( ! isset($data['method']))
		{
			$data['method'] = 'post';
		}
		
		$str = '';
		foreach ($data as $key => $val)
		{
			if ($key == 'action')
			{
				$str .= " {$key}='".BASE.AMP.$val.$this->url_append."'";
			}
			else
			{
				$str .= " {$key}='{$val}'";  
			}
		}
						
		$form = NL."<form{$str}>".NL;

		if ($this->EE->config->item('csrf_protection') == TRUE AND $data['method'] == 'post')
		{
			$hidden[$this->EE->security->csrf_token_name] = $this->EE->security->csrf_hash;		
		}

		// Load the form helper
		$this->EE->load->helper('form');

		
		if (count($hidden > 0))
		{
			foreach ($hidden as $key => $val)
			{
				$form .= "<div class='hidden'><input type='hidden' name='{$key}' value='".form_prep($val)."' /></div>".NL;
			}
		}
		
		return $form;
	}

	

	/** -----------------------------------
	/**  Form close
	/** -----------------------------------*/
	
	function form_close()
	{
		return "</form>".NL;
	}


	

	/** -------------------------------------------
	/**  Input - hidden
	/** -------------------------------------------*/
	
	function input_hidden($name, $value = '')
	{
		// Load the form helper
		$this->EE->load->helper('form');

		if ( ! is_array($name))
		{
			return "<div class='hidden'><input type='hidden' name='{$name}' value='".form_prep($value)."' /></div>".NL;
		}
	
		$form = '';
		
		foreach ($name as $key => $val)
		{
			$form .= "<div class='hidden'><input type='hidden' name='{$key}' value='".form_prep($val)."' /></div>".NL;
		}
		
		return $form;
	}



	
	/** -------------------------------------------
	/**  Input - text
	/** -------------------------------------------*/
	
	function input_text($name, $value='', $size = '90', $maxl = '100', $style='input', $width='100%', $extra = '', $convert = FALSE, $text_direction = 'ltr')
	{
		// Load the text and form helper
		$this->EE->load->helper(array('form', 'text'));

		$text_direction = ($text_direction == 'rtl') ? " dir='rtl' " : " dir='ltr' ";
		
		$value = ($convert == FALSE) ? form_prep($value) : form_prep(entities_to_ascii($value));
		
		$id = (stristr($extra, 'id=')) ? '' : "id='".str_replace(array('[',']'), '', $name)."'";
					
		return "<input {$text_direction} style='width:{$width}' type='text' name='{$name}' {$id} value='".$value."' size='{$size}' maxlength='{$maxl}' class='{$style}' $extra />".NL;
	}

 
	/** -------------------------------------------
	/**  Input - password
	/** -------------------------------------------*/
	
	function input_pass($name, $value='', $size = '20', $maxl = '100', $style='input', $width='100%', $text_direction = 'ltr')
	{		
		$text_direction = ($text_direction == 'rtl') ? " dir='rtl' " : " dir='ltr' ";
		
		$id = "id='".str_replace(array('[',']'), '', $name)."'";
		
		return "<input {$text_direction} style='width:{$width}' type='password' name='{$name}' {$id} value='{$value}' size='{$size}' maxlength='{$maxl}' class='{$style}' />".NL;
	}



	/** -------------------------------------------
	/**  Input - textarea
	/** -------------------------------------------*/
	
	function input_textarea($name, $value='', $rows = '20', $style='textarea', $width='100%', $extra = '', $convert = FALSE, $text_direction = 'ltr')
	{
		// Load the text helper
		$this->EE->load->helper(array('text', 'form'));

		$text_direction = ($text_direction == 'rtl') ? " dir='rtl' " : " dir='ltr' ";

		$value = ($convert == FALSE) ? form_prep($value) : form_prep(entities_to_ascii($value));
		
		$id = (stristr($extra, 'id=')) ? '' : "id='".str_replace(array('[',']'), '', $name)."'";

		return "<textarea {$text_direction} style='width:{$width};' name='{$name}' {$id} cols='90' rows='{$rows}' class='{$style}' $extra>".$value."</textarea>".NL;
	}



	/** -------------------------------------------
	/**  Input - pulldown - header
	/** -------------------------------------------*/
	
	function input_select_header($name, $multi = '', $size=3, $width='', $extra='')
	{
		if ($multi != '')
			$multi = " size='".$size."' multiple='multiple'";
			
		if ($multi == '')
		{
			$class = 'select';
		}
		else
		{
			$class = 'multiselect';  
			
			if ($width == '')
			{
				$width = '45%';
			}
		}
		
		if ($width != '')
		{
			$width = "style='width:".$width."'";
		}

		$extra = ($extra != '') ? ' '.trim($extra) : '';
		
		return NL."<select name='{$name}' class='{$class}'{$multi} {$width}{$extra}>".NL;
	}


	/** -------------------------------------------
	/**  Input - pulldown 
	/** -------------------------------------------*/
	
	function input_select_option($value, $item, $selected = '', $extra='')
	{
		$selected = ($selected != '') ? " selected='selected'" : '';
		$extra	= ($extra != '') ? " ".trim($extra)." " : '';
	
		return "<option value='".$value."'".$selected.$extra.">".$item."</option>".NL;
	}



	/** -------------------------------------------
	/**  Input - pulldown - footer
	/** -------------------------------------------*/
	
	function input_select_footer()
	{	
		return "</select>".NL;
	}



	/** -------------------------------------------
	/**  Input - checkbox
	/** -------------------------------------------*/
	
	function input_checkbox($name, $value='', $checked = '', $extra = '')
	{
		$checked = ($checked == '' OR $checked == 'n') ? '' : "checked='checked'";
		
		return "<input class='checkbox' type='checkbox' name='{$name}' value='{$value}' {$checked}{$extra} />".NL;
	}



	/** -------------------------------------------
	/**  Input - radio buttons
	/** -------------------------------------------*/
	
	function input_radio($name, $value='', $checked = 0, $extra = '')
	{
		$checked = ($checked == 0) ? '' : "checked='checked'";
	
		return "<input class='radio' type='radio' name='{$name}' value='{$value}' {$checked}{$extra} />".NL;
	}



	/** -------------------------------------------
	/**  Input - submit
	/** -------------------------------------------*/
	
	function input_submit($value='', $name = '', $extra='')
	{
		$value = ($value == '') ? $this->EE->lang->line('submit') : $value;
		$name  = ($name == '') ? '' : "name='".$name."'";
		
		if ($extra != '')
			$extra = ' '.$extra.' ';
	
		return NL."<input $name type='submit' class='submit' value='{$value}' {$extra} />".NL;
	}

	
	/** -------------------------------------------
	/**  Magic Checkboxes for Rows
	/** -------------------------------------------*/
	
	function magic_checkboxes()
	{
		ob_start();
		
		?>

<script type="text/javascript"> 
<!--

var lastChecked = '';

function magic_check()
{
	var listTable = document.getElementById('target').getElementsByTagName("table")[0];
	var listTRs = listTable.getElementsByTagName("tr");
	
	for (var j = 0; j < listTRs.length; j++)
	{
		var elements = listTRs[j].getElementsByTagName("td");
		
		for ( var i = 0; i < elements.length; i++ )
		{
			elements[i].onclick = function (e) {
			
									e = (e) ? e : ((window.event) ? window.event : "")
									var element = e.target OR e.srcElement;
									var tag = element.tagName ? element.tagName.toLowerCase() : null;
									
									// Last chance
									if (tag == null)
									{
										element = element.parentNode;
										tag = element.tagName ? element.tagName.toLowerCase() : null;
									}
			
									if (tag != 'a' && tag != null)
									{
										while (element.tagName.toLowerCase() != 'tr')
										{
											element = element.parentNode;
											if (element.tagName.toLowerCase() == 'a') return;
										}
										
										var theTDs = element.getElementsByTagName("td");
										var theInputs = element.getElementsByTagName("input");
										var entryID = false;
										var toggleFlag = false;
										
										for ( var k = 0; k < theInputs.length; k++ )
										{
											if (theInputs[k].type == "checkbox")
											{
												if (theInputs[k].name == 'toggleflag')
												{
													toggleFlag = true;
												}
												else
												{
													entryID = theInputs[k].id;
												}
												
												break;
											}
										}
										
										if (entryID == false && toggleFlag == false) return;
										
										// Select All Checkbox
										if (toggleFlag == true)
										{
											if (tag != 'input')
											{
												return;
											}
											
											var listTable = document.getElementById('target').getElementsByTagName("table")[0];
											var listTRs = listTable.getElementsByTagName("tr");
	
											for (var j = 1; j < listTRs.length; j++)
											{
												var elements = listTRs[j].getElementsByTagName("td");
		
												for ( var t = 0; t < elements.length; t++ )
												{
													if (theInputs[k].checked == true)
													{
														elements[t].className = (elements[t].className == 'tableCellOne') ? 'tableCellTwoHover' : 'tableCellOneHover';
													}
													else
													{
														elements[t].className = (elements[t].className == 'tableCellOneHover') ? 'tableCellOne' : 'tableCellTwo';
													}
												}
											}
										}
										else
										{
											if (tag != 'input')
											{
												document.getElementById(entryID).checked = (document.getElementById(entryID).checked ? false : true);
											}
										
											// Unselect any selected text on screen
											// Safari does not have this ability, which sucks
											// so I just did a focus();
											if (document.getSelection) { window.getSelection().removeAllRanges(); }
											else if (document.selection) { document.selection.empty(); }
											else { document.getElementById(entryID).focus(); }
											
											for ( var t = 0; t < theTDs.length; t++ )
											{
												if (document.getElementById(entryID).checked == true)
												{
													theTDs[t].className = (theTDs[t].className == 'tableCellOne') ? 'tableCellTwoHover' : 'tableCellOneHover';
												}
												else
												{
													theTDs[t].className = (theTDs[t].className == 'tableCellOneHover') ? 'tableCellOne' : 'tableCellTwo';
												}
											}
										
											if (e.shiftKey && lastChecked != '')
											{
												shift_magic_check(document.getElementById(entryID).checked, lastChecked, element);
											}
										
											lastChecked = element;
										}
									}
								}
		}
	}
}	


function shift_magic_check(whatSet, lastChecked, current)
{
	var outerElement = current.parentNode;
	var outerTag = outerElement.tagName ? outerElement.tagName.toLowerCase() : null;
	
	if (outerTag == null)
	{
		outerElement = outerElement.parentNode;
		outerTag = outerElement.tagName ? outerElement.tagName.toLowerCase() : null;
	}
	
	if (outerTag != null)
	{
		while (outerElement.tagName.toLowerCase() != 'table')
		{
			outerElement = outerElement.parentNode;
		}
		
		var listTRs = outerElement.getElementsByTagName("tr");
	
		var start = false;
	
		for (var j = 1; j < listTRs.length; j++)
		{
			if (start == false && listTRs[j] != lastChecked && listTRs[j] != current)
			{
				continue;
			}
			
			var listTDs = listTRs[j].getElementsByTagName("td");
			var listInputs = listTRs[j].getElementsByTagName("input");
			var entryID = false;
			
			for ( var k = 0; k < listInputs.length; k++ )
			{
				if (listInputs[k].type == "checkbox")
				{
					entryID = listInputs[k].id;
				}
			}
										
			if (entryID == false OR entryID == '') return;
			
			document.getElementById(entryID).checked = whatSet;
			
			for ( var t = 0; t < listTDs.length; t++ )
			{
				if (whatSet == true)
				{
					listTDs[t].className = (listTDs[t].className == 'tableCellOne') ? 'tableCellTwoHover' : 'tableCellOneHover';
				}
				else
				{
					listTDs[t].className = (listTDs[t].className == 'tableCellOneHover') ? 'tableCellOne' : 'tableCellTwo';
				}
			}
			
			if (listTRs[j] == lastChecked OR listTRs[j] == current)
			{
				if (start == true) break;
				if (start == false) start = true;
			}
		}
	}
}
	
//-->
</script>
		<?php
	
		$buffer = ob_get_contents();
				
		ob_end_clean(); 
		
		return $buffer;
	} 

		
	
	
	/** -------------------------------------------
	/**  JavaScript checkbox toggle code
	/** -------------------------------------------*/
	
	// This lets us check/uncheck all checkboxes in a series

	function toggle()
	{
		ob_start();
	
		?>
		<script type="text/javascript"> 
		<!--
	
		function toggle(thebutton)
		{
			if (thebutton.checked) 
			{
				val = true;
			}
			else
			{
				val = false;
			}
						
			var len = document.target.elements.length;
		
			for (var i = 0; i < len; i++) 
			{
				var button = document.target.elements[i];
				
				var name_array = button.name.split("["); 
				
				if (name_array[0] == "toggle") 
				{
					button.checked = val;
				}
			}
			
			document.target.toggleflag.checked = val;
		}
		
		//-->
		</script>
		<?php
	
		$buffer = ob_get_contents();
				
		ob_end_clean(); 
		
		return $buffer;
	} 

		
		
	
	// DEPRECATED DISPLAY FUNCTIONS
	// -----------------------------------------------------------------------
	// ----------------------------------------------------------------------
	
	// At present we're still using these so they have to stay here.
	// Once we've gone throgh the entire control panel and replaced these
	// function calls with the new versions we can kill them.
	
		
	function table($style='', $cellspacing='0', $cellpadding='0', $width='100%', $border='0', $align='')
	{
		$style	= ($style != '') ? " class='{$style}' " : '';
		$width	= ($width != '') ? " style='width:{$width};' " : '';
		$align	= ($align != '') ? " align='{$align}' " : '';
								
		if ($border == '')	  $border = 0;
		if ($cellspacing == '') $cellspacing = 0;
		if ($cellpadding == '') $cellpadding = 0;
		
		return NL."<table border='{$border}'  cellspacing='{$cellspacing}' cellpadding='{$cellpadding}'{$width}{$style}{$align}>".NL;
	}


	/** -------------------------------------------
	/**  Table "quick" row
	/** -------------------------------------------*/
	
	function table_qrow($style='', $data = '', $auto_width = FALSE)
	{
		$width = '';
		$style = ($style != '') ? " class='{$style}' " : '';
	
		if (is_array($data))
		{
			if ($auto_width != FALSE AND count($data) > 1)
			{
				$width = floor(100/count($data)).'%';
			}
			
			$width = ($width != '') ? " style='width:{$width};' " : '';
		
			$r = "<tr>";
			
			foreach($data as $val)
			{
				$r .=  "<td".$style.$width.">".
						$val.
						'</td>'.NL;	
			}
			
			$r .= "</tr>".NL;
			
			return $r;	  
		}
		else
		{
			return
			
				"<tr>".
				"<td".$style.$width.">".
				$data.
				'</td>'.NL.
				"</tr>".NL;	
		}
	}



	/** -------------------------------------------
	/**  Table "quick" cell
	/** -------------------------------------------*/
	function table_qcell($style = '', $data = '', $width = '', $valign = '', $align = '')
	{	
		if (is_array($data))
		{
			$r = '';
						
			foreach($data as $val)
			{
				$r .=  $this->td($style, $width, '', '', $valign, $align).
						$val.
						$this->td_c();	
			}
			
			return $r;	  
		}
		else
		{
			return
			
				$this->td($style, $width, '', '', $valign, $align).
				$data.
				$this->td_c();	
		}
	}



	/** -------------------------------------------
	/**  Table row start
	/** -------------------------------------------*/
	
	function tr($style='')
	{
		return "<tr>";
	}

	
	/** -------------------------------------------
	/**  Table data cell
	/** -------------------------------------------*/
	
	function td($style='', $width='', $colspan='', $rowspan='', $valign = '', $align = '')
	{
		if ($style  == '') 
			$style = 'default';
		
		if ($style != 'none')
		{
			$style = " class='".$style."' ";
		}
		
		$width	= ($width	!= '') ? " style='width:{$width};'" : '';
		$colspan = ($colspan != '') ? " colspan='{$colspan}'"	: '';
		$rowspan = ($rowspan != '') ? " rowspan='{$rowspan}'"	: '';
		$valign  = ($valign  != '') ? " valign='{$valign}'"	 : '';
		$align	= ($align  != '') 	? " align='{$align}'"		: '';
		
		return $this->nl()."<td ".$style.$width.$colspan.$rowspan.$valign.$align.">".$this->nl();
	}


	/** -------------------------------------------
	/**  Table cell close
	/** -------------------------------------------*/
	
	function td_c()
	{
		return $this->nl().'</td>';
	}

	
	/** -------------------------------------------
	/**  Table row close
	/** -------------------------------------------*/
	
	function tr_c()
	{
		return $this->nl().'</tr>'.$this->nl();
	}

	
	
	/** -------------------------------------------
	/**  Table close
	/** -------------------------------------------*/
	
	function table_c()
	{
		return '</table>'.$this->nl(2);
	}


	/** -------------------------------------------
	/**  Form declaration -- old version
	/** -------------------------------------------*/
	
	function form($action, $name = '', $method = 'post', $extras = '')
	{
		if ($name != '')
			$name = " name='{$name}' id='{$name}' ";
			
		if ($method != '')
			$method = 'post';
				
		return $this->nl()."<form method='{$method}' ".$name." action='".BASE.AMP.$action.$this->url_append."' $extras>".$this->nl();
	}

	
	
	/** -------------------------------------------
	/**  Form close
	/** -------------------------------------------*/
	
	function form_c()
	{
		return "</form>".NL;
	}


	// END DEPRECATED DISPLAY FUNCTIONS	
	// -----------------------------------------------------------------------
	// -----------------------------------------------------------------------
	
	
	/* -------------------------------------
	/*  Validate Channel and Member Custom Fields
	/*  
	/*  Yes this method doesn't strictly have anything to do with
	/*  displaying anything, but here it will be available for use
	/*  in the control panel by anyone who needs it.
	/* -------------------------------------*/
	
	function invalid_custom_field_names()
	{
		$channel_vars = array(
								'aol_im', 'author', 'author_id', 'avatar_image_height',
								'avatar_image_width', 'avatar_url', 'bday_d', 'bday_m',
								'bday_y', 'bio', 'comment_auto_path',
								'comment_entry_id_auto_path', 
								'comment_total', 'comment_url_title_path', 'count',
								'edit_date', 'email', 'entry_date', 'entry_id',
								'entry_id_path', 'expiration_date', 'forum_topic_id',
								'gmt_edit_date', 'gmt_entry_date', 'icq', 'interests',
								'ip_address', 'location', 'member_search_path', 'msn_im',
								'occupation', 'permalink', 'photo_image_height',
								'photo_image_width', 'photo_url', 'profile_path',
								'recent_comment_date', 'relative_date', 'relative_url',
								'screen_name', 'signature', 'signature_image_height',
								'signature_image_url', 'signature_image_width', 'status',
								'switch', 'title', 'title_permalink', 'total_results', 'url',
								'url_as_email_as_link', 'url_or_email', 'url_or_email_as_author',
								'url_title', 'url_title_path', 'username', 'channel',
								'channel_id', 'yahoo_im', 
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
						
		return array_unique(array_merge($channel_vars, $global_vars, $orderby_vars));
	}
	/* END */
	
	/** -----------------------------------------------------------
    /**  Prepares text for use as an HTML attribute
    /** -----------------------------------------------------------*/
    //  Prevents user-defined labels from breaking out of crucial HTML markup
    //-----------------------------------------------------------
    
	function html_attribute_prep($label, $quotes = ENT_QUOTES)
	{
		// to prevent a PHP warning, we need to check that their system charset is one
		// that is accepted by htmlspecialchars().  Unlike the native function, however,
		// we default to UTF-8 instead of ISO-8859-1 if it's not an available charset.
		$charset = (in_array(strtoupper($this->EE->config->item('charset')),
										array(
												'ISO-8859-1', 'ISO8859-1', 
												'ISO-8859-15', 'ISO8859-15',
												'UTF-8',
												'CP866', 'IBM866', '866',
												'CP1251', 'WINDOWS-1251', 'WIN-1251', '1251',
												'CP1252', 'WINDOWS-1252', '1252',
												'KOI8-R', 'KOI8-RU', 'KOI8R',
												'BIG5', '950',
												'GB2312', '936',
												'BIG5-HKSCS',
												'SHIFT_JIS', 'SJIS', '932',
												'EUC-JP', 'EUCJP'
					  						)
					)) ? $this->EE->config->item('charset') : 'UTF-8';
			
		return htmlspecialchars($label, $quotes, $charset);
	}
	/* END */
	
	
	
}
// END CLASS

/* End of file display.php */
/* Location: ./system/expressionengine/controllers/cp/display.php */