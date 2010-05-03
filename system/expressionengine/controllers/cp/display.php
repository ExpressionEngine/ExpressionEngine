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
	/**  Fetch CSS Stylesheet - used by spellcheck
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
	/**  Mailto Tag
	/** -------------------------------------------*/
	
	function mailto($email, $name = "")
	{
		if ($name == "") $name = $email;

		return "<a href='mailto:{$email}'>$name</a>";
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
}
// END CLASS

/* End of file display.php */
/* Location: ./system/expressionengine/controllers/cp/display.php */