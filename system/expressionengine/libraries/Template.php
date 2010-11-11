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
 * ExpressionEngine Template Parser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Template {
		
	var $loop_count	  		=	0;			// Main loop counter.	
	var $depth				=	0;			// Sub-template loop depth
	var $in_point			=  '';			// String position of matched opening tag
	var $template			=  '';			// The requested template (page)
	var $final_template	 	=  '';			// The finalized template
	var $fl_tmpl		 	=  '';			// 'Floating' copy of the template.  Used as a temporary "work area".
	var $cache_hash	  		=  '';			// md5 checksum of the template name.  Used as title of cache file.
	var $cache_status		=  '';			// Status of page cache (NO_CACHE, CURRENT, EXPIRED)
	var $tag_cache_status	=  '';			// Status of tag cache (NO_CACHE, CURRENT, EXPIRED)
	var $cache_timestamp	=  ''; 
	var $template_type  	=  '';			// Type of template (webpage, rss)
	var $embed_type			=  '';			// Type of template for embedded template
	var $template_hits		=	0;
	var $php_parse_location =  'output';	// Where in the chain the PHP gets parsed
	var $template_edit_date	=	'';			// Template edit date
	var $templates_sofar	=   '';			// Templates processed so far, subtemplate tracker
	var $encode_email		=  TRUE;		// Whether to use the email encoder.  This is set automatically
	var $hit_lock_override	=  FALSE;		// Set to TRUE if you want hits tracked on sub-templates
	var $hit_lock			=  FALSE;		// Lets us lock the hit counter if sub-templates are contained in a template
	var $parse_php			=  FALSE;		// Whether to parse PHP or not
	var $protect_javascript =  TRUE;		// Protect javascript in conditionals
	
	var $tag_data			= array();		// Data contained in tags
	var $modules		 	= array();		// List of installed modules
	var $module_data		= array();		// Data for modules from exp_channels
	var $plugins		 	= array();		// List of installed plug-ins
	
	var $var_single	  		= array();		// "Single" variables
	var $var_cond			= array();		// "Conditional" variables
	var $var_pair			= array();		// "Paired" variables
	var $global_vars		= array();		// This array can be set via the path.php file
	var $embed_vars		 	= array();		// This array can be set via the {embed} tag
	var $segment_vars		= array();		// Array of segment variables
		
	var $tagparts			= array();		// The parts of the tag: {exp:comment:form}
	var $tagdata			= '';			// The chunk between tag pairs.  This is what modules will utilize
	var $tagproper			= '';			// The full opening tag
	var $no_results			= '';			// The contents of the {if no_results}{/if} conditionals
	var $no_results_block	= '';			// The {if no_results}{/if} chunk
	var $search_fields		= array();		// Special array of tag parameters that begin with 'search:'
	var $date_vars			= array();		// Date variables found in the tagdata (FALSE if date variables do not exist in tagdata)
	var $unfound_vars		= array();		// These are variables that have not been found in the tagdata and can be ignored
	var $conditional_vars	= array();		// Used by the template variable parser to prep conditionals
	var $TYPE				= FALSE;		// FALSE if Typography has not been instantiated, Typography Class object otherwise 
	
	var $related_data		= array();		//  A multi-dimensional array containing any related tags
	var $related_id			= '';			// Used temporarily for the related ID number
	var $related_markers	= array();		// Used temporarily
	
	var $site_ids			= array();		// Site IDs for the Sites Request for a Tag
	var $sites				= array();		// Array of sites with site_id as key and site_name as value, used to determine site_ids for tag, above.
	var $site_prefs_cache	= array();		// Array of cached site prefs, to allow fetching of another site's template files
	
	var $reverse_related_data = array();	//  A multi-dimensional array containing any reverse related tags
	
	var $t_cache_path		= 'tag_cache/';	 // Location of the tag cache file
	var $p_cache_path		= 'page_cache/'; // Location of the page cache file
	var $disable_caching	= FALSE;
	
	var $debugging			= FALSE;		// Template parser debugging on?
	var $cease_processing	= FALSE;		// Used with no_results() method.
	var $log				= array();		// Log of Template processing
	var $start_microtime	= 0;			// For Logging (= microtime())

    var $strict_urls		= FALSE;		// Whether to make URLs operate strictly or not.  This is set via a template global pref
	
	var $realm				= 'ExpressionEngine Template';  // Localize?

	var $marker = '0o93H7pQ09L8X1t49cHY01Z5j4TT91fGfr'; // Temporary marker used as a place-holder for template data

	var $form_id			= '';		// 	Form Id
	var $form_class 		= '';		// 	Form Class

	// --------------------------------------------------------------------
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		if ($this->EE->config->item('multiple_sites_enabled') != 'y')
		{
			$this->sites[$this->EE->config->item('site_id')] = $this->EE->config->item('site_short_name');
		}
		
		if ($this->EE->config->item('template_debugging') === 'y' && $this->EE->session->userdata['group_id'] == 1)
		{
			$this->debugging = TRUE;
			
			if (phpversion() < 5)
			{
				list($usec, $sec) = explode(" ", microtime());
				$this->start_microtime = ((float)$usec + (float)$sec);
			}
			else
			{
				$this->start_microtime = microtime(TRUE);
			}
		}
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Run Template Engine
	 *
	 * Upon a Page or a Preview, it Runs the Processing of a Template baed on URI request or method arguments
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */	
	 
	function run_template_engine($template_group = '', $template = '')
	{
		$this->log_item(" - Begin Template Processing - ");
				
		// Set the name of the cache folder for both tag and page caching
		
		if ($this->EE->uri->uri_string != '')
		{		  
			$this->t_cache_path .= md5($this->EE->functions->fetch_site_index().$this->EE->uri->uri_string).'/';
			$this->p_cache_path .= md5($this->EE->functions->fetch_site_index().$this->EE->uri->uri_string).'/';		
		}
		else
		{
			$this->t_cache_path .= md5($this->EE->config->item('site_url').'index'.$this->EE->uri->query_string).'/';
			$this->p_cache_path .= md5($this->EE->config->item('site_url').'index'.$this->EE->uri->query_string).'/';
		}
		
		
		// We limit the total number of cache files in order to
		// keep some sanity with large sites or ones that get
		// hit by over-ambitious crawlers.
		
		if ($this->disable_caching == FALSE)
		{		
			if ($dh = @opendir(APPPATH.'cache/page_cache'))
			{
				$i = 0;
				while (FALSE !== (readdir($dh)))
				{
					$i++;
				}
				
				$max = ( ! $this->EE->config->item('max_caches') OR ! is_numeric($this->EE->config->item('max_caches')) OR $this->EE->config->item('max_caches') > 1000) ? 1000 : $this->EE->config->item('max_caches');
				
				if ($i > $max)
				{
					$this->EE->functions->clear_caching('page');
				}			
			}	
		}
		
		$this->log_item("URI: ".$this->EE->uri->uri_string);
		$this->log_item("Path.php Template: {$template_group}/{$template}");
		
		$this->fetch_and_parse($template_group, $template, FALSE);
		
		$this->log_item(" - End Template Processing - ");
		$this->log_item("Parse Global Variables");

		if ($this->template_type == 'static')
		{
			$this->final_template = $this->restore_xml_declaration($this->final_template);
		}
		else
		{
			$this->final_template = $this->parse_globals($this->final_template);
		}
		
		$this->log_item("Template Parsing Finished");
	
		$this->EE->output->out_type = $this->template_type;
		$this->EE->output->set_output($this->final_template); 
	}
	
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch and Process Template
	 * 
	 * Determines what template to process, fetches the template and its preferences, and then processes all of it
	 * 
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	int
	 * @return	void
	 */
	function fetch_and_parse($template_group = '', $template = '', $sub = FALSE, $site_id = '')
	{
		// add this template to our subtemplate tracker
		$this->templates_sofar = $this->templates_sofar.'|'.$site_id.':'.$template_group.'/'.$template.'|';
		
		/** -------------------------------------
		/**  Fetch the requested template
		/** -------------------------------------*/
		
		// The template can either come from the DB or a cache file
		// Do not use a reference!
		
		$this->cache_status = 'NO_CACHE';
		
		$this->log_item("Retrieving Template");
								
		$this->template = ($template_group != '' AND $template != '') ? $this->fetch_template($template_group, $template, FALSE, $site_id) : $this->parse_template_uri();
			
		$this->log_item("Template Type: ".$this->template_type);
		
		$this->parse($this->template, $sub, $site_id);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Parse a string as a template
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */	
	function parse(&$str, $sub = FALSE, $site_id = '')
	{
		if ($str != '')
		{
			$this->template =& $str;
		}

		/** -------------------------------------
		/**  Static Content, No Parsing
		/** -------------------------------------*/
				
		if ($this->template_type == 'static' OR $this->embed_type == 'static')
		{
			if ($sub == FALSE)
			{
				$this->final_template = $this->template;
			}
			
			return;
		}
		
		/* -------------------------------------
		/*  "Smart" Static Parsing
		/*  
		/*  Performed on embedded webpage templates only that do not have 
		/*	ExpressionEngine tags or PHP in them.
		/*  
		/*  Hidden Configuration Variable
		/*  - smart_static_parsing => Bypass parsing of templates that could be 
		/*	of the type 'static' but aren't? (y/n)
		/* -------------------------------------*/
		if ($this->EE->config->item('smart_static_parsing') !== 'n' && $this->embed_type == 'webpage' && ! stristr($this->template, LD) && ! stristr($this->template, '<?'))
		{
			$this->log_item("Smart Static Parsing Triggered");

			if ($sub == FALSE)
			{
				$this->final_template = $this->template;
			}
						
			return;
		}
		
		/** --------------------------------------------------
		/**  Parse 'Site' variables
		/** --------------------------------------------------*/
		$this->log_item("Parsing Site Variables");
		
		// load site variables into the global_vars array
		foreach (array('site_id', 'site_label', 'site_short_name') as $site_var)
		{
			$this->EE->config->_global_vars[$site_var] = stripslashes($this->EE->config->item($site_var));
		}
		
		// Parse {last_segment} variable
		$seg_array = $this->EE->uri->segment_array();
		$this->EE->config->_global_vars['last_segment'] = end($seg_array);

		/** -------------------------------------
		/**  Parse manual variables and Snippets
		/** -------------------------------------*/
		
		// These are variables that can be set in the path.php file
		
		if (count($this->EE->config->_global_vars) > 0)
		{
			$this->log_item("Snippets (Keys): ".implode('|', array_keys($this->EE->config->_global_vars)));
			$this->log_item("Snippets (Values): ".trim(implode('|', $this->EE->config->_global_vars)));
		
			foreach ($this->EE->config->_global_vars as $key => $val)
			{
				$this->template = str_replace(LD.$key.RD, $val, $this->template);
			}
			
			// in case any of these variables have EE comments of their own
			$this->template = $this->remove_ee_comments($this->template);
		}
			
		/** -------------------------------------
		/**  Parse URI segments
		/** -------------------------------------*/
		
		// This code lets admins fetch URI segments which become
		// available as:  {segment_1} {segment_2}		
				
		for ($i = 1; $i < 10; $i++)
		{
			$this->template = str_replace(LD.'segment_'.$i.RD, $this->EE->uri->segment($i), $this->template); 
			$this->segment_vars['segment_'.$i] = $this->EE->uri->segment($i);
		}
		
		/** -------------------------------------
		/**  Parse {embed} tag variables
		/** -------------------------------------*/
		
		if ($sub === TRUE && count($this->embed_vars) > 0)
		{
			$this->log_item("Embed Variables (Keys): ".implode('|', array_keys($this->embed_vars)));
			$this->log_item("Embed Variables (Values): ".trim(implode('|', $this->embed_vars)));
		
			foreach ($this->embed_vars as $key => $val)
			{
				// add 'embed:' to the key for replacement and so these variables work in conditionals
				$this->embed_vars['embed:'.$key] = $val;
				unset($this->embed_vars[$key]);
				$this->template = str_replace(LD.'embed:'.$key.RD, $val, $this->template); 
			}
		}
		
		// cleanup of leftover/undeclared embed variables
		// don't worry with undeclared embed: vars in conditionals as the conditionals processor will handle that adequately
		if (strpos($this->template, LD.'embed:') !== FALSE)
		{
			$this->template = preg_replace('/'.LD.'embed:(.+?)'.RD.'/', '', $this->template);
		}		
	
		/** -------------------------------------
		/**  Parse date format string "constants"
		/** -------------------------------------*/
		
		$date_constants	= array('DATE_ATOM'		=>	'%Y-%m-%dT%H:%i:%s%Q',
								'DATE_COOKIE'	=>	'%l, %d-%M-%y %H:%i:%s UTC',
								'DATE_ISO8601'	=>	'%Y-%m-%dT%H:%i:%s%O',
								'DATE_RFC822'	=>	'%D, %d %M %y %H:%i:%s %O',
								'DATE_RFC850'	=>	'%l, %d-%M-%y %H:%m:%i UTC',
								'DATE_RFC1036'	=>	'%D, %d %M %y %H:%i:%s %O',
								'DATE_RFC1123'	=>	'%D, %d %M %Y %H:%i:%s %O',
								'DATE_RFC2822'	=>	'%D, %d %M %Y %H:%i:%s %O',
								'DATE_RSS'		=>	'%D, %d %M %Y %H:%i:%s %O',
								'DATE_W3C'		=>	'%Y-%m-%dT%H:%i:%s%Q'
								);
		foreach ($date_constants as $key => $val)
		{
			$this->template = str_replace(LD.$key.RD, $val, $this->template);
		}
		
		$this->log_item("Parse Date Format String Constants");
		
		/** --------------------------------------------------
		/**  Template's Last Edit time {template_edit_date format="%Y %m %d %H:%i:%s"}
		/** --------------------------------------------------*/
		if (strpos($this->template, LD.'template_edit_date') !== FALSE && preg_match_all("/".LD."template_edit_date\s+format=([\"\'])([^\\1]*?)\\1".RD."/", $this->template, $matches))
		{	
			for ($j = 0; $j < count($matches[0]); $j++)
			{				
				$this->template = str_replace($matches[0][$j], $this->EE->localize->decode_date($matches[2][$j], $this->template_edit_date), $this->template);				
			}
		}  

		/** --------------------------------------------------
		/**  Current time {current_time format="%Y %m %d %H:%i:%s"}
		/** --------------------------------------------------*/
		if (strpos($this->template, LD.'current_time') !== FALSE && preg_match_all("/".LD."current_time\s+format=([\"\'])([^\\1]*?)\\1".RD."/", $this->template, $matches))
		{				
			for ($j = 0; $j < count($matches[0]); $j++)
			{				
				$this->template = str_replace($matches[0][$j], $this->EE->localize->decode_date($matches[2][$j], $this->EE->localize->now), $this->template);	
			}
		}
		
		$this->template = str_replace(LD.'current_time'.RD, $this->EE->localize->now, $this->template);
		
		$this->log_item("Parse Current Time Variables");
		
		/** -------------------------------------
		/**  Is the main template cached?
		/** -------------------------------------*/
		// If a cache file exists for the primary template
		// there is no reason to go further.
		// However we do need to fetch any subtemplates
		
		if ($this->cache_status == 'CURRENT' AND $sub == FALSE)
		{
			$this->log_item("Cached Template Used");
		
			$this->template = $this->parse_nocache($this->template);
			
			/** -------------------------------------
			/**  Smite Our Enemies:  Advanced Conditionals
			/** -------------------------------------*/
			
			if (stristr($this->template, LD.'if'))
			{
				$this->template = $this->advanced_conditionals($this->template);
			}
			
			$this->log_item("Conditionals Parsed, Processing Sub Templates");
		
			$this->final_template = $this->template;
			$this->process_sub_templates($this->template); 	
			return;
		}
		
		// Remove whitespace from variables.
		// This helps prevent errors, particularly if PHP is used in a template
		$this->template = preg_replace("/".LD."\s*(\S+)\s*".RD."/U", LD."\\1".RD, $this->template);
		
		/** -------------------------------------
		/**  Parse Input Stage PHP
		/** -------------------------------------*/
		
		if ($this->parse_php == TRUE AND $this->php_parse_location == 'input' AND $this->cache_status != 'CURRENT')
		{
			$this->log_item("Parsing PHP on Input");
			$this->template = $this->parse_template_php($this->template);	
		}
		
		/** -------------------------------------
		/**  Smite Our Enemies:  Conditionals
		/** -------------------------------------*/
		
		$this->log_item("Parsing Segment, Embed, and Global Vars Conditionals");
		
		$this->template = $this->parse_simple_segment_conditionals($this->template);
		$this->template = $this->simple_conditionals($this->template, $this->embed_vars);
		$this->template = $this->simple_conditionals($this->template, $this->EE->config->_global_vars);

		/** -------------------------------------
		/**  Assign Variables
		/** -------------------------------------*/

		if (strpos($this->template, 'preload_replace') !== FALSE)
		{	
			if (preg_match_all("/".LD."preload_replace:(.+?)=([\"\'])([^\\2]*?)\\2".RD."/i", $this->template, $matches))
			{
				$this->log_item("Processing Preload Text Replacements: ".trim(implode('|', $matches[1])));
			
				for ($j = 0; $j < count($matches[0]); $j++)
				{				
					$this->template = str_replace($matches[0][$j], "", $this->template);
					$this->template = str_replace(LD.$matches[1][$j].RD, $matches[3][$j], $this->template);
				}
			}
		}
		
		
		/** -------------------------------------
		/**  Parse Plugin and Module Tags
		/** -------------------------------------*/

		$this->tags();
		
		if ($this->cease_processing === TRUE)
		{
			return;
		}
		
		/** -------------------------------------
		/**  Parse Output Stage PHP
		/** -------------------------------------*/
		
		if ($this->parse_php == TRUE AND $this->php_parse_location == 'output' AND $this->cache_status != 'CURRENT')
		{
			$this->log_item("Parsing PHP on Output");
			$this->template = $this->parse_template_php($this->template);	
		}
					
		/** -------------------------------------
		/**  Write the cache file if needed
		/** -------------------------------------*/
				
		if ($this->cache_status == 'EXPIRED')
		{ 
			$this->template = $this->EE->functions->insert_action_ids($this->template);
			$this->write_cache_file($this->cache_hash, $this->template, 'template');
		}
		
		/** -------------------------------------
		/**  Parse Our Uncacheable Forms
		/** -------------------------------------*/
		
		$this->template = $this->parse_nocache($this->template);
		
		/** -------------------------------------
		/**  Smite Our Enemies:  Advanced Conditionals
		/** -------------------------------------*/
		
		if (strpos($this->template, LD.'if') !== FALSE)
		{
			$this->log_item("Processing Advanced Conditionals");
			$this->template = $this->advanced_conditionals($this->template);
		}
		
		/** -------------------------------------
		/**  Build finalized template
		/** -------------------------------------*/
		
		// We only do this on the first pass.
		// The sub-template routine will insert embedded
		// templates into the master template
		
		if ($sub == FALSE)
		{
			$this->final_template = $this->template;
			$this->process_sub_templates($this->template); 
		}
	 }
		
	// --------------------------------------------------------------------
	
	/**
	 * Processes Any Embedded Templates in String
	 *
	 * If any {embed=} tags are found, it processes those templates and does a replacement.
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */	
	 
	function process_sub_templates($template)
	{
		/** -------------------------------------
		/**  Match all {embed=bla/bla} tags
		/** -------------------------------------*/
		
		$matches = array();
	
		if ( ! preg_match_all("/(".LD."embed\s*=)(.*?)".RD."/s", $template, $matches))
		{
			return;
		}
		
		/** -------------------------------------
		/**  Loop until we have parsed all sub-templates
		/** -------------------------------------*/
		
		// For each embedded tag we encounter we'll run the template parsing
		// function - AND - through the beauty of recursive functions we
		// will also call THIS function as well, allowing us to parse 
		// infinitely nested sub-templates in one giant loop o' love
		
		$this->log_item(" - Processing Sub Templates (Depth: ".($this->depth+1).") - ");
		
		$i = 0;
		$this->depth++;
		
		$this->log_item("List of Embeds: ".str_replace(array('"', "'"), '', trim(implode(',', $matches[2]))));
		
		// re-match the full tag of each if necessary before we start processing
		// necessary evil in case template globals are used inside the embed tag,
		// doing this within the processing loop will result in leaving unparsed
		// embed tags e.g. {embed="foo/bar" var="{global_var}/{custom_field}"}
		$temp = $template;
		foreach ($matches[2] as $key => $val)
		{
			if (strpos($val, LD) !== FALSE)
			{
				$matches[0][$key] = $this->EE->functions->full_tag($matches[0][$key], $temp);
				$matches[2][$key] = substr(str_replace($matches[1][$key], '', $matches[0][$key]), 0, -1);
				$temp = str_replace($matches[0][$key], '', $temp);
			}
		}
		
		// Load the string helper
		$this->EE->load->helper('string');

		foreach($matches[2] as $key => $val)
		{
			$parts = preg_split("/\s+/", $val, 2);

			$this->embed_vars = (isset($parts[1])) ? $this->EE->functions->assign_parameters($parts[1]) : array();
			
			if ($this->embed_vars === FALSE)
			{
				$this->embed_vars = array();
			}
			
			$val = trim_slashes(strip_quotes($parts[0]));

			if (strpos($val, '/') === FALSE)
			{	
				continue;
			}
	
			$ex = explode("/", trim($val));
			
			if (count($ex) != 2)
			{
				continue;
			}
			
			/** ----------------------------------
			/**  Determine Site
			/** ----------------------------------*/
			
			$site_id = $this->EE->config->item('site_id');
			
			if (stristr($ex[0], ':'))
			{
				$name = substr($ex[0], 0, strpos($ex[0], ':'));
				
				if ($this->EE->config->item('multiple_sites_enabled') == 'y' && ! IS_FREELANCER)
				{
					if (count($this->sites) == 0)
					{
						// This should really be cached somewhere
						$this->EE->db->select('site_id, site_name');
						$sites_query = $this->EE->db->get('sites');
						
						foreach($sites_query->result_array() as $row)
						{
							$this->sites[$row['site_id']] = $row['site_name'];
						}
					}
					
					$site_id = array_search($name, $this->sites);
					
					if (empty($site_id))
					{
						$site_id = $this->EE->config->item('site_id');
					}
				}
				
				$ex[0] = str_replace($name.':', '', $ex[0]);
			}
			
			
			/** ----------------------------------
			/**  Loop Prevention
			/** ----------------------------------*/

			/* -------------------------------------------
			/*	Hidden Configuration Variable
			/*	- template_loop_prevention => 'n' 
				Whether or not loop prevention is enabled - y/n
			/* -------------------------------------------*/
					
			if (substr_count($this->templates_sofar, '|'.$site_id.':'.$ex['0'].'/'.$ex['1'].'|') > 1 && $this->EE->config->item('template_loop_prevention') != 'n')
			{
				$this->final_template = ($this->EE->config->item('debug') >= 1) ? str_replace('%s', $ex['0'].'/'.$ex['1'], $this->EE->lang->line('template_loop')) : "";
				return;				
			}
				
			/** ----------------------------------
			/**  Process Subtemplate
			/** ----------------------------------*/
			
			$this->log_item("Processing Sub Template: ".$ex[0]."/".$ex[1]);
				
			$this->fetch_and_parse($ex[0], $ex[1], TRUE, $site_id);
			
			$this->final_template = str_replace($matches[0][$key], $this->template, $this->final_template);
			
			$this->embed_type = '';
			
			// Here we go again!  Wheeeeeee.....				
			$this->process_sub_templates($this->template);

			// pull the subtemplate tracker back a level to the parent template
			$this->templates_sofar = substr($this->templates_sofar, 0, - strlen('|'.$site_id.':'.$ex[0].'/'.$ex[1].'|'));
		}
				
		$this->depth--;
		
		if ($this->depth == 0)
		{
			$this->templates_sofar = '';
		}
	}


	// --------------------------------------------------------------------
	
	/**
	 * Finds Tags, Parses Them
	 *
	 * Goes Through the Template, Finds the Beginning and End of Tags, and Stores Tag Data in a Class Array
	 *
	 * @access	public
	 * @return	void
	 */	
	 
	function parse_tags()
	{
		while (TRUE)  
		{
			// Make a "floating" copy of the template which we'll progressively slice into pieces with each loop
			
			$this->fl_tmpl = $this->template;
					
			// Identify the string position of the first occurence of a matched tag
			
			$this->in_point = strpos($this->fl_tmpl, LD.'exp:');
							  
			// If the above variable returns FALSE we are done looking for tags
			// This single conditional keeps the template engine from spiraling 
			// out of control in an infinite loop.		
			
			if (FALSE === $this->in_point)
			{
				break;
			}
			else
			{
				/** ------------------------------------------
				/**  Process the tag data
				/** ------------------------------------------*/
				
				// These REGEXs parse out the various components contained in any given tag.
				
				// Grab the opening portion of the tag: {exp:some:tag param="value" param="value"}

				if ( ! preg_match("/".LD.'exp:'.".*?".RD."/s", $this->fl_tmpl, $matches))
				{
					$this->template = preg_replace("/".LD.'exp:'.".*?$/", '', $this->template);
					break;
				}
		
				// Checking for variables/tags embedded within tags
				// {exp:channel:entries channel="{master_channel_name}"}				
				if (stristr(substr($matches[0], 1), LD) !== FALSE)
				{
					$matches[0] = $this->EE->functions->full_tag($matches[0]);
				}

				$this->log_item("Tag: ".$matches[0]);
											
				$raw_tag = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $matches[0]);
								
				$tag_length = strlen($raw_tag);
				
				$data_start = $this->in_point + $tag_length;

				$tag  = trim(substr($raw_tag, 1, -1));
				$args = trim((preg_match("/\s+.*/", $tag, $matches))) ? $matches[0] : '';
				$tag  = trim(str_replace($args, '', $tag));  
				
				$cur_tag_close = LD.'/'.$tag.RD;
				
				// Deprecate "weblog" tags, but allow them to work until 2.1, then remove this.
				if (strpos($tag, ':weblog:') !== FALSE OR strpos($tag, ' weblog=') !== FALSE)
				{
					$tag = str_replace(array(':weblog:', ' weblog='), array(':channel:', ' channel='), $tag);
					$this->log_item("WARNING: Deprecated 'weblog' tag used, please change to 'channel'");
				}
				
				// -----------------------------------------
				// Grab the class name and method names contained in the tag
				
				$class = explode(':', substr($tag, strlen('exp') + 1));
				
				// Tags can either have one segment or two:
				// {exp:first_segment}
				// {exp:first_segment:second_segment}
				//
				// These two segments represent either a "class:constructor"
				// or a "class:method".  We need to determine which one it is.
				
				if (count($class) == 1)
				{
					$class[1] = $class[0];
				}
				
				foreach($class as $key => $value)
				{
					$class[$key] = trim($value);
				}
				
				// -----------------------------------------

				// Assign parameters based on the arguments from the tag
				$args  = $this->EE->functions->assign_parameters($args);

				// standardized mechanism for "search" type parameters get some extra lovin'
				$search_fields = array();
				
				if ($args !== FALSE)
				{
					foreach ($args as $key => $val)
					{
						if (strncmp($key, 'search:', 7) == 0)
						{
							$search_fields[substr($key, 7)] = $val;
						}
					}					
				}
				
				// Trim the floating template, removing the tag we just parsed.
				
				$this->fl_tmpl = substr($this->fl_tmpl, $this->in_point + $tag_length);
				
				$out_point = strpos($this->fl_tmpl, $cur_tag_close);
				
				// Do we have a tag pair?
				
				if (FALSE !== $out_point)
				{
					// Assign the data contained between the opening/closing tag pair
					
					$this->log_item("Closing Tag Found");
				
					$block = substr($this->template, $data_start, $out_point);  
					
					// Fetch the "no_results" data
					
					$no_results = '';
					$no_results_block = '';
					
					if (strpos($block, 'if no_results') !== FALSE && preg_match("/".LD."if no_results".RD."(.*?)".LD.'\/'."if".RD."/s", $block, $match)) 
					{
						// Match the entirety of the conditional, dude.  Bad Rick!
						
						if (stristr($match[1], LD.'if'))
						{
							$match[0] = $this->EE->functions->full_tag($match[0], $block, LD.'if', LD.'\/'."if".RD);
						}
						
						$no_results = substr($match[0], strlen(LD."if no_results".RD), -strlen(LD.'/'."if".RD));

						$no_results_block = $match[0];
					}
							
					// Define the entire "chunk" - from the left edge of the opening tag 
					// to the right edge of closing tag.

					$out_point = $out_point + $tag_length + strlen($cur_tag_close);
										
					$chunk = substr($this->template, $this->in_point, $out_point);
				}
				else
				{
					// Single tag...
					
					$this->log_item("No Closing Tag");
					
					$block = ''; // Single tags don't contain data blocks
					
					$no_results = '';
					$no_results_block = '';
				
					// Define the entire opening tag as a "chunk"
				
					$chunk = substr($this->template, $this->in_point, $tag_length);				
				}
								
				// Strip the "chunk" from the template, replacing it with a unique marker.
				
				if (stristr($raw_tag, 'random'))
				{
					$this->template = preg_replace("|".preg_quote($chunk)."|s", 'M'.$this->loop_count.$this->marker, $this->template, 1);
				}
				else
				{
					$this->template = str_replace($chunk, 'M'.$this->loop_count.$this->marker, $this->template);
				}
				
				$cfile = md5($chunk); // This becomes the name of the cache file

				// Build a multi-dimensional array containing all of the tag data we've assembled

				$this->tag_data[$this->loop_count]['tag']				= $raw_tag;
				$this->tag_data[$this->loop_count]['class']				= $class[0];
				$this->tag_data[$this->loop_count]['method']			= $class[1];
				$this->tag_data[$this->loop_count]['tagparts']			= $class;
				$this->tag_data[$this->loop_count]['params']			= $args;
				$this->tag_data[$this->loop_count]['chunk']				= $chunk; // Matched data block - including opening/closing tags		  
				$this->tag_data[$this->loop_count]['block']				= $block; // Matched data block - no tags
				$this->tag_data[$this->loop_count]['cache']				= $args;
				$this->tag_data[$this->loop_count]['cfile']				= $cfile;
				$this->tag_data[$this->loop_count]['no_results']		= $no_results;
				$this->tag_data[$this->loop_count]['no_results_block']	= $no_results_block;
				$this->tag_data[$this->loop_count]['search_fields']		= $search_fields;

			} // END IF

		  // Increment counter			
		  $this->loop_count++;  

		} // END WHILE
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Looks Through Template Looking for Tags
	 *
	 * Goes Through the Template, Finds the Beginning and End of Tags, and Stores Tag Data in a Class Array
	 *
	 * @access	public
	 * @return	void
	 */	

	function tags()
	{
		// Fetch installed modules and plugins if needed
		if (count($this->modules) == 0)
		{
			$this->fetch_addons();
		}

		// Parse the template.

		$this->log_item(" - Beginning Tag Processing - ");

		while (is_int(strpos($this->template, LD.'exp:')))
		{
			// Initialize values between loops
			$this->tag_data 	= array();
			$this->var_single	= array();
			$this->var_cond		= array();
			$this->var_pair		= array(); 
			$this->loop_count 	= 0;
			
			$this->log_item("Parsing Tags in Template");

			// Run the template parser
			$this->parse_tags();

			$this->log_item("Processing Tags");
			
			// Run the class/method handler
			$this->process_tags();
			
			if ($this->cease_processing === TRUE)
			{
				return;
			}
		}
		
		$this->log_item(" - End Tag Processing - ");
	}


	// --------------------------------------------------------------------
	
	/**
	 * Process Tags
	 *
	 * Takes the Class Array Full of Tag Data and Processes the Tags One by One.  Class class, feeds
	 * data to class, takes results, and puts it back into the Template.
	 *
	 * @access	public
	 * @return	void
	 */	
	 
	function process_tags()
	{
		$plugins = array();
		$modules = array();
		
		// Fill an array with the names of all the classes that we previously extracted from the tags
		
		for ($i = 0, $ctd = count($this->tag_data); $i < $ctd; $i++)
		{
			// Check the tag cache file
			$cache_contents = $this->fetch_cache_file($this->tag_data[$i]['cfile'], 'tag', $this->tag_data[$i]['cache']);
			
			// Set cache status for final processing
			$this->tag_data[$i]['cache'] = $this->tag_cache_status;

			if ($this->tag_cache_status == 'CURRENT')
			{
				// If so, replace the marker in the tag with the cache data
				
				$this->log_item("Tag Cached and Cache is Current");
				
				$this->template = str_replace('M'.$i.$this->marker, $cache_contents, $this->template);
			}
			else
			{
				// Is a module or plug-in being requested?
			
				if ( ! in_array($this->tag_data[$i]['class'] , $this->modules))
				{				 
					if ( ! in_array($this->tag_data[$i]['class'] , $this->plugins))
					{					
						
						$this->log_item("Invalid Tag");

						if ($this->EE->config->item('debug') >= 1)
						{
							if ($this->tag_data[$i]['tagparts'][0] == $this->tag_data[$i]['tagparts'][1] &&
								! isset($this->tag_data[$i]['tagparts'][2]))
							{
								unset($this->tag_data[$i]['tagparts'][1]);
							}
						
							$error  = $this->EE->lang->line('error_tag_syntax');
							$error .= '<br /><br />';
							$error .= htmlspecialchars(LD);
							$error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);				
							$error .= htmlspecialchars(RD);
							$error .= '<br /><br />';
							$error .= $this->EE->lang->line('error_fix_syntax');
			
							$this->EE->output->fatal_error($error);						 
						}
						else
							return FALSE;			 
					}
					else
					{
						$plugins[] = $this->tag_data[$i]['class'];
						$this->log_item("Plugin Tag: ".ucfirst($this->tag_data[$i]['class']).'/'.$this->tag_data[$i]['method']);
					}
				}
				else
				{
					$modules[] = $this->tag_data[$i]['class'];
					$this->log_item("Module Tag: ".ucfirst($this->tag_data[$i]['class']).'/'.$this->tag_data[$i]['method']);
				}
			}
		}

		// Remove duplicate class names and re-order the array
		
		$plugins = array_values(array_unique($plugins));
		$modules = array_values(array_unique($modules));
		
		// Dynamically require the file that contains each class
		
		$this->log_item("Including Files for Plugins and Modules");
		
		foreach ($plugins as $plugin)
		{
			// make sure it's not already included just in case
			if ( ! class_exists($plugin))
			{
				if (in_array($plugin ,$this->EE->core->native_plugins))
				{
					require_once PATH_PI."pi.{$plugin}".EXT;
				}
				else
				{
					require_once PATH_THIRD."{$plugin}/pi.{$plugin}".EXT;
				}
			}
		}
		
		foreach ($modules as $module)
		{
			// make sure it's not already included just in case
			if ( ! class_exists($module))
			{
				if (in_array($module, $this->EE->core->native_modules))
				{
					require_once PATH_MOD."{$module}/mod.{$module}".EXT;
				}
				else
				{
					require_once PATH_THIRD."{$module}/mod.{$module}".EXT;
				}
			}			
		}
		
		$this->log_item("Files for Plugins and Modules All Included");
		
		/** -----------------------------------
		/**  Only Retrieve Data if Not Done Before and Modules Being Called
		/** -----------------------------------*/
		
		if (count($this->module_data) == 0 && count(array_intersect($this->modules, $modules)) > 0)
		{
			$this->EE->db->select('module_version, module_name');
			$query = $this->EE->db->get('modules');

			foreach($query->result_array() as $row)
			{
				$this->module_data[$row['module_name']] = array('version' => $row['module_version']);
			}
		}
				
		// Final data processing

		// Loop through the master array containing our extracted template data
		
		$this->log_item("Beginning Final Tag Data Processing");
		
		reset($this->tag_data);

		for ($i = 0; $i < count($this->tag_data); $i++)
		{			
			if ($this->tag_data[$i]['cache'] != 'CURRENT')
			{
				$this->log_item("Calling Class/Method: ".ucfirst($this->tag_data[$i]['class'])."/".$this->tag_data[$i]['method']);
			
				/* ---------------------------------
				/*  Plugin as Parameter
				/*
				/*  - Example: channel="{exp:some_plugin}"
				/*  - A bit of a hidden feature.  Has been tested but not quite
				/*  ready to say it is ready for prime time as I might want to 
				/*  move it to earlier in processing so that if there are 
				/*  multiple plugins being used as parameters it is only called
				/*  once instead of for every single parameter. - Paul
				/* ---------------------------------*/
				
				if (substr_count($this->tag_data[$i]['tag'], LD.'exp') > 1 && isset($this->tag_data[$i]['params']['parse']) && $this->tag_data[$i]['params']['parse'] == 'inward')
				{
					foreach($this->tag_data[$i]['params'] as $name => $param)
					{
						if (stristr($this->tag_data[$i]['params'][$name], LD.'exp'))
						{
							$this->log_item("Plugin in Parameter, Processing Plugin First");
						
							$TMPL2 = clone $this;

							while (is_int(strpos($TMPL2->tag_data[$i]['params'][$name], LD.'exp:')))
							{
								unset($this->EE->TMPL);
								$this->EE->TMPL = new EE_Template();
								$this->EE->TMPL->start_microtime = $this->start_microtime;
								$this->EE->TMPL->template = $TMPL2->tag_data[$i]['params'][$name];
								$this->EE->TMPL->tag_data	= array();
								$this->EE->TMPL->var_single = array();
								$this->EE->TMPL->var_cond	= array();
								$this->EE->TMPL->var_pair	= array();
								$this->EE->TMPL->plugins = $TMPL2->plugins;
								$this->EE->TMPL->modules = $TMPL2->modules;
								$this->EE->TMPL->parse_tags();
								$this->EE->TMPL->process_tags();
								$this->EE->TMPL->loop_count = 0;
								$TMPL2->tag_data[$i]['params'][$name] = $this->EE->TMPL->template;
								$TMPL2->log = array_merge($TMPL2->log, $this->EE->TMPL->log);
							}
							
							foreach (get_object_vars($TMPL2) as $key => $value)
							{
								$this->$key = $value;
							}
							
							unset($TMPL2);
						
							$this->EE->TMPL = $this;
						}
					}
				}
			
				/** ---------------------------------
				/**  Nested Plugins...
				/** ---------------------------------*/
				
				if (in_array($this->tag_data[$i]['class'] , $this->plugins) && strpos($this->tag_data[$i]['block'], LD.'exp:') !== FALSE)
				{
					if ( ! isset($this->tag_data[$i]['params']['parse']) OR $this->tag_data[$i]['params']['parse'] != 'inward')
					{
						$this->log_item("Nested Plugins in Tag, Parsing Outward First");
						
						$TMPL2 = clone $this;

						while (is_int(strpos($TMPL2->tag_data[$i]['block'], LD.'exp:')))
						{
							unset($this->EE->TMPL);
							$this->EE->TMPL = new EE_Template();
							$this->EE->TMPL->start_microtime = $this->start_microtime;
							$this->EE->TMPL->template = $TMPL2->tag_data[$i]['block'];
							$this->EE->TMPL->tag_data	= array();
							$this->EE->TMPL->var_single = array();
							$this->EE->TMPL->var_cond	= array();
							$this->EE->TMPL->var_pair	= array();
							$this->EE->TMPL->plugins = $TMPL2->plugins;
							$this->EE->TMPL->modules = $TMPL2->modules;
							$this->EE->TMPL->parse_tags();
							$this->EE->TMPL->process_tags();
							$this->EE->TMPL->loop_count = 0;
							$TMPL2->tag_data[$i]['block'] = $this->EE->TMPL->template;
							$TMPL2->log = array_merge($TMPL2->log, $this->EE->TMPL->log);
						}

						foreach (get_object_vars($TMPL2) as $key => $value)
						{
							$this->$key = $value;
						}
						
						unset($TMPL2);
					
						$this->EE->TMPL = $this;
					}
				}

				// Assign the data chunk, parameters
				
				// We moved the no_results_block here because of nested tags. The first 
				// parsed tag has priority for that conditional.
				$this->tagdata			= str_replace($this->tag_data[$i]['no_results_block'], '', $this->tag_data[$i]['block']);
				$this->tagparams 		= $this->tag_data[$i]['params']; 
				$this->tagchunk 	 	= $this->tag_data[$i]['chunk'];
				$this->tagproper		= $this->tag_data[$i]['tag'];
				$this->tagparts			= $this->tag_data[$i]['tagparts'];
				$this->no_results		= $this->tag_data[$i]['no_results'];
				$this->search_fields	= $this->tag_data[$i]['search_fields'];

				/** -------------------------------------
				/**  Assign Sites for Tag
				/** -------------------------------------*/
				
				$this->_fetch_site_ids();
				
				/** -------------------------------------
				/**  Fetch Form Class/Id Attributes
				/** -------------------------------------*/
				$this->tag_data[$i] = $this->_assign_form_params($this->tag_data[$i]);

				/** -------------------------------------
				/**  Relationship Data Pulled Out
				/** -------------------------------------*/
				
				// If the channel:entries tag or search:search_results is being called
				// we need to extract any relationship data that might be present.
				// Note: This needs to happen before extracting the variables
				// in the tag so it doesn't get confused as to which entry the
				// variables belong to.
				
				if (($this->tag_data[$i]['class'] == 'channel' AND $this->tag_data[$i]['method'] == 'entries')
					OR ($this->tag_data[$i]['class'] == 'search' AND $this->tag_data[$i]['method'] == 'search_results'))
				{				
					$this->tagdata = $this->assign_relationship_data($this->tagdata);
				}		 

				// LEGACY CODE
				// Fetch the variables for this particular tag
				// Hopefully, with Jones' new parsing code we should be able to stop using the
				// assign_variables and assign_conditional_variables() methods entirely. -Paul
															  
				$vars = $this->EE->functions->assign_variables($this->tag_data[$i]['block']);
				
				if (count($this->related_markers) > 0)
				{
					foreach ($this->related_markers as $mkr)
					{
						if ( ! isset($vars['var_single'][$mkr]))
						{
							$vars['var_single'][$mkr] = $mkr;
						}
					}
				}

				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];

				if ($this->related_id != '')
				{
					$this->var_single[$this->related_id] = $this->related_id;
					$this->related_id = '';
				}
				
				// Assign the class name and method name
			
				$class_name = ucfirst($this->tag_data[$i]['class']);
				$meth_name  = $this->tag_data[$i]['method'];
				
				
				// If it's a third party class or a first party module,
				// add the root folder to the loader paths so we can use
				// libraries, models, and helpers
				
				$package_path = '';
				
				if ( ! in_array($this->tag_data[$i]['class'], $this->EE->core->native_plugins))
				{
					$package_path = in_array($this->tag_data[$i]['class'], $this->EE->core->native_modules) ? PATH_MOD : PATH_THIRD;
					$package_path .= strtolower($this->tag_data[$i]['class'].'/');
					
					$this->EE->load->add_package_path($package_path);
				}
				
				// Dynamically instantiate the class.
				// If module, only if it is installed...
				
				if (in_array($this->tag_data[$i]['class'], $this->modules) && ! isset($this->module_data[$class_name]))
				{
					$this->log_item("Problem Processing Module: Module Not Installed");
				}
				else
				{
					$this->log_item(" -> Class Called: ".$class_name);
					
					$EE = new $class_name();
				}
				
				/** ----------------------------------
				/**  Does method exist?  Is This A Module and Is It Installed?
				/** ----------------------------------*/
		
				if ((in_array($this->tag_data[$i]['class'], $this->modules) && ! isset($this->module_data[$class_name])) OR ! method_exists($EE, $meth_name))
				{
					
					$this->log_item("Tag Not Processed: Method Inexistent or Module Not Installed");

					if ($this->EE->config->item('debug') >= 1)
					{						
						if ($this->tag_data[$i]['tagparts'][0] == $this->tag_data[$i]['tagparts'][1] &&
							! isset($this->tag_data[$i]['tagparts'][2]))
						{
							unset($this->tag_data[$i]['tagparts'][1]);
						}
						
						$error  = $this->EE->lang->line('error_tag_module_processing');
						$error .= '<br /><br />';
						$error .= htmlspecialchars(LD);
						$error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);				 
						$error .= htmlspecialchars(RD);
						$error .= '<br /><br />';
						$error .= str_replace('%x', $this->tag_data[$i]['class'], str_replace('%y', $meth_name, $this->EE->lang->line('error_fix_module_processing')));
						
						$this->EE->output->fatal_error($error);
					 }
					 else
					{
						return;						
					}
				}	
				
				/*
				
				OK, lets grab the data returned from the class.
				
				First, however, lets determine if the tag has one or two segments.  
				If it only has one, we don't want to call the constructor again since
				it was already called during instantiation.
		 
				Note: If it only has one segment, only the object constructor will be called.
				Since constructors can't return a value just by initialializing the object
				the output of the class must be assigned to a variable called $this->return_data
								
				*/
				
				$this->log_item(" -> Method Called: ".$meth_name);
				
				if (strtolower($class_name) == $meth_name)
				{
					$return_data = (isset($EE->return_data)) ? $EE->return_data : '';
				}
				else
				{
					$return_data = $EE->$meth_name();
				}

				// if it's a third party add-on or module, remove the temporarily added path for local libraries, models, etc.
				// if a "no results" template is returned, $this->tag_data will be reset inside of the scope
				// of the tag being processed.  So let's use the locally scoped variable for the class name
				
				if ($package_path)
				{
					$this->EE->load->remove_package_path();
				}

				/** ----------------------------------
				/**  404 Page Triggered, Cease All Processing of Tags From Now On
				/** ----------------------------------*/
				
				if ($this->cease_processing === TRUE)
				{
					return;
				}
				
				$this->log_item(" -> Data Returned");
			  
				// Write cache file if needed
				
				if ($this->tag_data[$i]['cache'] == 'EXPIRED')
				{
					$this->write_cache_file($this->tag_data[$i]['cfile'], $return_data);
				}

				// Replace the temporary markers we added earlier with the fully parsed data
				
				$this->template = str_replace('M'.$i.$this->marker, $return_data, $this->template);
				
				// Initialize data in case there are susequent loops
				
				$this->var_single = array();
				$this->var_cond	= array();
				$this->var_pair	= array();
				
				unset($return_data);
				unset($class_name);
				unset($meth_name);	
				unset($EE);
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Process Tags
	 *
	 * Channel entries can have related entries embedded within them.
	 * We'll extract the related tag data, stash it away in an array, and
	 * replace it with a marker string so that the template parser
	 * doesn't see it.  In the channel class we'll check to see if the 
	 * $this->EE->TMPL->related_data array contains anything.  If so, we'll celebrate
	 * wildly.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	

	function assign_relationship_data($chunk)
	{
		$this->related_markers = array();
		
			if (preg_match_all("/".LD."related_entries\s+id\s*=\s*[\"\'](.+?)[\"\']".RD."(.+?)".LD.'\/'."related_entries".RD."/is", $chunk, $matches))
		{  		
			$this->log_item("Assigning Related Entry Data");
			
			$no_rel_content = '';
			
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$rand = $this->EE->functions->random('alnum', 8);
				$marker = LD.'REL['.$matches[1][$j].']'.$rand.'REL'.RD;
				
				if (preg_match("/".LD."if no_related_entries".RD."(.*?)".LD.'\/'."if".RD."/s", $matches[2][$j], $no_rel_match)) 
				{
					// Match the entirety of the conditional
					
					if (stristr($no_rel_match[1], LD.'if'))
					{
						$match[0] = $this->EE->functions->full_tag($no_rel_match[0], $matches[2][$j], LD.'if', LD.'\/'."if".RD);
					}
					
					$no_rel_content = substr($no_rel_match[0], strlen(LD."if no_related_entries".RD), -strlen(LD.'/'."if".RD));
				}
				
				$this->related_markers[] = $matches[1][$j];
				$vars = $this->EE->functions->assign_variables($matches[2][$j]);
				$this->related_id = $matches[1][$j];
				$this->related_data[$rand] = array(
											'marker'			=> $rand,
											'field_name'		=> $matches[1][$j],
											'tagdata'			=> $matches[2][$j],
											'var_single'		=> $vars['var_single'],
											'var_pair' 			=> $vars['var_pair'],
											'var_cond'			=> $this->EE->functions->assign_conditional_variables($matches[2][$j], '\/', LD, RD),
											'no_rel_content'	=> $no_rel_content
										);
										
				$chunk = str_replace($matches[0][$j], $marker, $chunk);					
			}
		}

		if (preg_match_all("/".LD."reverse_related_entries\s*(.*?)".RD."(.+?)".LD.'\/'."reverse_related_entries".RD."/is", $chunk, $matches))
		{  		
			$this->log_item("Assigning Reverse Related Entry Data");
		
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$rand = $this->EE->functions->random('alnum', 8);
				$marker = LD.'REV_REL['.$rand.']REV_REL'.RD;
				$vars = $this->EE->functions->assign_variables($matches[2][$j]);
				
				$no_rev_content = '';

				if (preg_match("/".LD."if no_reverse_related_entries".RD."(.*?)".LD.'\/'."if".RD."/s", $matches[2][$j], $no_rev_match)) 
				{
					// Match the entirety of the conditional
					
					if (stristr($no_rev_match[1], LD.'if'))
					{
						$match[0] = $this->EE->functions->full_tag($no_rev_match[0], $matches[2][$j], LD.'if', LD.'\/'."if".RD);
					}
					
					$no_rev_content = substr($no_rev_match[0], strlen(LD."if no_reverse_related_entries".RD), -strlen(LD.'/'."if".RD));
				}
				
				$this->reverse_related_data[$rand] = array(
															'marker'			=> $rand,
															'tagdata'			=> $matches[2][$j],
															'var_single'		=> $vars['var_single'],
															'var_pair' 			=> $vars['var_pair'],
															'var_cond'			=> $this->EE->functions->assign_conditional_variables($matches[2][$j], '\/', LD, RD),
															'params'			=> $this->EE->functions->assign_parameters($matches[1][$j]),
															'no_rev_content'	=> $no_rev_content
														);
										
				$chunk = str_replace($matches[0][$j], $marker, $chunk);					
			}
		}
	
		return $chunk;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Parameter for Tag
	 *
	 * Used by Modules to fetch a paramter for the tag currently be processed.  We also have code 
	 * in here to convert legacy values like 'y' and 'on' to their more respectable full values.
	 * Further, if one assigns the second argument, it will be returned as the value if a
	 * parameter of the $which name does not exist for this tag.  Handy for default values!
	 *
	 * @access	public
	 * @access	string
	 * @access	bool
	 * @return	string
	 */
		
	function fetch_param($which, $default = FALSE)
	{
		if ( ! isset($this->tagparams[$which]))
		{
			return $default;
		}
		else
		{
			// Making yes/no tag parameters consistent.  No "y/n" or "on/off".
			switch($this->tagparams[$which])
			{
				case 'y'	:
				case 'on'	:
					return 'yes';
				break;
				case 'n'	:
				case 'off'	:
					return 'no';
				break;
				default		:
					return $this->tagparams[$which];	
				break;
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Replace a Single Variable with Its Value
	 *
	 * LEGACY!!!
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	 
	function swap_var_single($search, $replace, $source)
	{
		return str_replace(LD.$search.RD, $replace, $source);  
	}


	// --------------------------------------------------------------------
	
	/**
	 * Seems to Take a Variable Pair and Replace it With Its COntents
	 *
	 * LEGACY!!!
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	 
	function swap_var_pairs($open, $close, $source)
	{
		return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", "\\1", $source); 
	}


	// --------------------------------------------------------------------
	
	/**
	 * Completely Removes a Variable Pair
	 *
	 * LEGACY!!!
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	 
	function delete_var_pairs($open, $close, $source)
	{
		return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", "", $source); 
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetches Variable Pair's Content
	 *
	 * LEGACY!!!
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	 
	function fetch_data_between_var_pairs($str, $variable)
	{
		if ($str == '' OR $variable == '')
			return;
		
		if ( ! preg_match("/".LD.$variable.".*?".RD."(.*?)".LD.'\/'.$variable.RD."/s", $str, $match))
				return;
 
		return $match[1];		
	}


	// --------------------------------------------------------------------
	
	/**
	 * Returns String with PHP Processed
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	 
	 function parse_template_php($str)
	 {
		ob_start();

		echo $this->EE->functions->evaluate($str);
		
		$str = ob_get_contents();
		
		ob_end_clean(); 
		
		$this->parse_php = FALSE;
		
		return $str;
	 }

	// --------------------------------------------------------------------
	
	/**
	 * Get Cache File Data
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	mixed
	 * @return	string
	 */
	function fetch_cache_file($cfile, $cache_type = 'tag', $args = array())
	{
		// Which cache are we working on?
		$status = ($cache_type == 'tag') ? 'tag_cache_status' : 'cache_status';
		$status =& $this->$status;
		
		if ( ! isset($args['cache']) OR $args['cache'] != 'yes')
		{
			$status = 'NO_CACHE';
			return FALSE;
		}

		$cache_dir = ($cache_type == 'tag') ? APPPATH.'cache/'.$this->t_cache_path : $cache_dir = APPPATH.'cache/'.$this->p_cache_path;
		$file = $cache_dir.$cfile;
		
		if ( ! file_exists($file) OR ! ($fp = @fopen($file, FOPEN_READ)))
		{
			$status = 'EXPIRED';
			return FALSE;
		}
		
		$cache = '';
		$refresh = ( ! isset($args['refresh'])) ? 0 : $args['refresh']; 
		
		flock($fp, LOCK_SH);
		
		// Read the first line (left a small buffer - just in case)
		$timestamp	= trim(fgets($fp, 30));
		
		if ((strlen($timestamp) != 10) OR ($timestamp !== ((string)(int) $timestamp))) // Integer check
		{
			// Should never happen - so we'll log it
			$this->log_item("Invalid Cache File Format: ".$file);
			$status = 'EXPIRED';
		}
		elseif (time() > ($timestamp + ($refresh * 60)))
		{
			$status = 'EXPIRED';
		}
		else
		{
			// Timestamp valid - read rest of file
			$status = 'CURRENT';
			$cache = @fread($fp, filesize($file));
		}
		
		flock($fp, LOCK_UN);
		fclose($fp);
		
		return $cache;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Write Data to Cache File
	 *
	 * Stores the Tag and Page Cache Data
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function write_cache_file($cfile, $data, $cache_type = 'tag')
	{
		if ($this->disable_caching == TRUE)
		{
			return;
		}

		/* -------------------------------------
		/*  Disable Tag Caching
		/*  
		/*  All for you, Nevin!  Disables tag caching, which if used unwisely
		/*  on a high traffic site can lead to disastrous disk i/o
		/*  This setting allows quick thinking admins to temporarily disable
		/*  it without hacking or modifying folder permissions
		/*  
		/*  Hidden Configuration Variable
		/*  - disable_tag_caching => Disable tag caching? (y/n)
		/* -------------------------------------*/

		if ($cache_type == 'tag' && $this->EE->config->item('disable_tag_caching') == 'y')
		{
			return;
		}
	
		$cache_dir  = ($cache_type == 'tag') ? APPPATH.'cache/'.$this->t_cache_path : $cache_dir = APPPATH.'cache/'.$this->p_cache_path;
		$cache_base = ($cache_type == 'tag') ? APPPATH.'cache/tag_cache' : APPPATH.'cache/page_cache';
				
		$cache_file = $cache_dir.$cfile;
		
		$dirs = array($cache_base, $cache_dir);
		
		foreach ($dirs as $dir)
		{		
			if ( ! @is_dir($dir))
			{
				if ( ! @mkdir($dir, DIR_WRITE_MODE))
				{
					return;
				}
				
				if ($dir == $cache_base && $fp = @fopen($dir.'/index.html', FOPEN_WRITE_CREATE_DESTRUCTIVE))
				{
					fclose($fp);					
				}
				
				@chmod($dir, DIR_WRITE_MODE);			
			}
		}

		if ( ! $fp = @fopen($cache_file, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			$this->log_item("Could not create/write to cache file: ".$cache_file);
			return;
		}

		flock($fp, LOCK_EX);
		if (fwrite($fp, time()."\n".$data) === FALSE)
		{
			$this->log_item("Could not write to cache file: ".$cache_file);
		}
		flock($fp, LOCK_UN);
		fclose($fp);
		
		@chmod($cache_file, FILE_WRITE_MODE);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Parse Template URI
	 *
	 * Determines Which Template to Fetch Based on the Page's URI.
	 * If invalid Template, shows Template Group's index page
	 * If invalid Template Group, depending on sendings may show 404 or default Template Group
	 *
	 * @access	public
	 * @return	string
	 */
    function parse_template_uri()
    {
        $this->log_item("Parsing Template URI");
        
        // Does the first segment exist?  No?  Show the default template   
        if ($this->EE->uri->segment(1) === FALSE)
        {     
			return $this->fetch_template('', 'index', TRUE);
        }
        // Is only the pagination showing in the URI?
        elseif(count($this->EE->uri->segments) == 1 && preg_match("#^(P\d+)$#", $this->EE->uri->segment(1), $match))
        {
        	$this->EE->uri->query_string = $match['1'];
        	return $this->fetch_template('', 'index', TRUE);
        }
        
        // Set the strict urls pref
        if ($this->EE->config->item('strict_urls') !== FALSE)
        {
        	$this->strict_urls = ($this->EE->config->item('strict_urls') == 'y') ? TRUE : FALSE;
        }

		// Load the string helper
		$this->EE->load->helper('string');
		
        // At this point we know that we have at least one segment in the URI, so  
		// let's try to determine what template group/template we should show
		
		// Is the first segment the name of a template group?
		$this->EE->db->select('group_id');
		$this->EE->db->where('group_name', $this->EE->uri->segment(1));
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('template_groups');
	
		// Template group found!
		if ($query->num_rows() == 1)
		{
			// Set the name of our template group
			$template_group = $this->EE->uri->segment(1);

			$this->log_item("Template Group Found: ".$template_group);
			
			// Set the group_id so we can use it in the next query
			$group_id = $query->row('group_id');
		
			// Does the second segment of the URI exist? If so...
			if ($this->EE->uri->segment(2) !== FALSE)
			{
				// Is the second segment the name of a valid template?
				$this->EE->db->select('COUNT(*) as count');
				$this->EE->db->where('group_id', $group_id);
				$this->EE->db->where('template_name', $this->EE->uri->segment(2));
				$query = $this->EE->db->get('templates');
			
				// We have a template name!
				if ($query->row('count') == 1)
				{
					// Assign the template name
					$template = $this->EE->uri->segment(2);
					
					// Re-assign the query string variable in the Input class so the various tags can show the correct data
					$this->EE->uri->query_string = ( ! $this->EE->uri->segment(3) AND $this->EE->uri->segment(2) != 'index') ? '' : trim_slashes(substr($this->EE->uri->uri_string, strlen('/'.$this->EE->uri->segment(1).'/'.$this->EE->uri->segment(2))));
				}
				else // A valid template was not found
				{
					// is there a file we can automatically create this template from?
					if ($this->EE->config->item('save_tmpl_files') == 'y' && $this->EE->config->item('tmpl_file_basepath') != '')
					{
						if ($this->_create_from_file($template_group, $this->EE->uri->segment(2)))
						{
							return $this->fetch_template($template_group, $this->EE->uri->segment(2), FALSE);
						}
					}
					
					// Set the template to index		
					$template = 'index';
				   
					// Re-assign the query string variable in the Input class so the various tags can show the correct data
					$this->EE->uri->query_string = ( ! $this->EE->uri->segment(3)) ? $this->EE->uri->segment(2) : trim_slashes(substr($this->EE->uri->uri_string, strlen('/'.$this->EE->uri->segment(1))));
				}
			}
			// The second segment of the URL does not exist
			else
			{
				// Set the template as "index"
				$template = 'index';
			}
		}
		// The first segment in the URL does NOT correlate to a valid template group.  Oh my!
		else 
		{
			// If we are enforcing strict URLs we need to show a 404
			if ($this->strict_urls == TRUE)
			{
				// is there a file we can automatically create this template from?
				if ($this->EE->config->item('save_tmpl_files') == 'y' && $this->EE->config->item('tmpl_file_basepath') != '')
				{
					if ($this->_create_from_file($this->EE->uri->segment(1), $this->EE->uri->segment(2)))
					{
						return $this->fetch_template($this->EE->uri->segment(1), $this->EE->uri->segment(2), FALSE);
					}
				}

				if ($this->EE->config->item('site_404'))
				{
					$this->log_item("Template group and template not found, showing 404 page");
					return $this->fetch_template('', '', FALSE);
				}
				else
				{
					return $this->_404();
				}
			}
			
			// We we are not enforcing strict URLs, so Let's fetch the the name of the default template group
			$this->EE->db->select('group_name, group_id');
			$this->EE->db->where('is_site_default', 'y');
			$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
			$result = $this->EE->db->get('template_groups');

			// No result?  Bail out...
			// There's really nothing else to do here.  We don't have a valid template group in the URL
			// and the admin doesn't have a template group defined as the site default.
			if ($result->num_rows() == 0)
			{
				// Turn off caching 
				$this->disable_caching = TRUE;

				// Show the user-specified 404
				if ($this->EE->config->item('site_404'))
				{
					$this->log_item("Template group and template not found, showing 404 page");
					return $this->fetch_template('', '', FALSE);
				}
				else
				{
					// Show the default 404
					return $this->_404();
				}
			}
			
			// Since the first URI segment isn't a template group name, 
			// could it be the name of a template in the default group?
			$this->EE->db->select('COUNT(*) as count');
			$this->EE->db->where('group_id', $result->row('group_id'));
			$this->EE->db->where('template_name', $this->EE->uri->segment(1));
			$query = $this->EE->db->get('templates');

			// We found a valid template!
			if ($query->row('count') == 1)
			{ 
				// Set the template group name from the prior query result (we use the default template group name)
				$template_group	= $result->row('group_name');

				$this->log_item("Template Group Using Default: ".$template_group);

				// Set the template name
				$template = $this->EE->uri->segment(1);				

				// Re-assign the query string variable in the Input class so the various tags can show the correct data
				if ($this->EE->uri->segment(2))
				{
					$this->EE->uri->query_string = trim_slashes(substr($this->EE->uri->uri_string, strlen('/'.$this->EE->uri->segment(1))));
				}			
			}
			// A valid template was not found.  At this point we do not have either a valid template group or a valid template name in the URL
			else
			{
				// is there a file we can automatically create this template from?
				if ($this->EE->config->item('save_tmpl_files') == 'y' && $this->EE->config->item('tmpl_file_basepath') != '')
				{
					if ($this->_create_from_file($this->EE->uri->segment(1), $this->EE->uri->segment(2)))
					{
						return $this->fetch_template($this->EE->uri->segment(1), $this->EE->uri->segment(2), FALSE);
					}
				}
				
				// Turn off caching 
				$this->disable_caching = TRUE;

				// is 404 preference set, we wet our group/template names as blank.
				// The fetch_template() function below will fetch the 404 and show it
				if ($this->EE->config->item('site_404'))
				{
					$template_group = '';
					$template = '';
					$this->log_item("Template group and template not found, showing 404 page");
				}
				else
				// No 404 preference is set so we will show the index template from the default template group
				{
					$this->EE->uri->query_string = trim_slashes($this->EE->uri->uri_string);
					$template_group	= $result->row('group_name');
					$template = 'index';
					$this->log_item("Showing index. Template not found: ".$this->EE->uri->segment(1));
				}
			}		
		}

		// Fetch the template!
       return $this->fetch_template($template_group, $template, FALSE);
    }
   // END
   
	// --------------------------------------------------------------------
	
	/**
	 * 404 Page
	 *
	 * If users do not have a 404 template specified this is what gets shown
	 *
	 * @access	private
	 * @return	string
	 */
	function _404()
	{
		$this->log_item("404 Page Returned");
		$this->EE->output->set_status_header(404);
		echo '<html><head><title>404 Page Not Found</title></head><body><h1>Status: 404 Page Not Found</h1></body></html>';
		exit;	
	}
	// END

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Template Data
	 *
	 * Takes a Template Group, Template, and Site ID and will retrieve the Template and its metadata
	 * from the database (or file)
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	int
	 * @return	string
	 */
	 
	function fetch_template($template_group, $template, $show_default = TRUE, $site_id = '')
	{
		if ($site_id == '' OR ! is_numeric($site_id))
		{
			$site_id = $this->EE->config->item('site_id');
		}
		
		$this->log_item("Retrieving Template from Database: ".$template_group.'/'.$template);
		 
		$sql_404 = '';
		$template_group_404 = '';
		$template_404 = '';

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- hidden_template_indicator => '.' 
			The character(s) used to designate a template as "hidden"
		/* -------------------------------------------*/
	
		$hidden_indicator = ($this->EE->config->item('hidden_template_indicator') === FALSE) ? '.' : $this->EE->config->item('hidden_template_indicator');			
		
		if ($this->depth == 0 AND substr($template, 0, 1) == $hidden_indicator)
		{			
			/* -------------------------------------------
			/*	Hidden Configuration Variable
			/*	- hidden_template_404 => y/n 
				If a hidden template is encountered, the default behavior is
				to throw a 404.  With this set to 'n', the template group's
				index page will be shown instead
			/* -------------------------------------------*/
			
			if ($this->EE->config->item('hidden_template_404') !== 'n')
			{				
				$x = explode("/", $this->EE->config->item('site_404'));
				
				if (isset($x[0]) AND isset($x[1]))
				{
					$this->EE->output->out_type = '404';
					$this->template_type = '404';
					
					$template_group_404 = $this->EE->db->escape_str($x[0]);
					$template_404 = $this->EE->db->escape_str($x[1]);
					
					$sql_404 = " AND exp_template_groups.group_name='".$this->EE->db->escape_str($x[0])."' AND exp_templates.template_name='".$this->EE->db->escape_str($x[1])."'";
				}
				else
				{
					$template = 'index';
				}
			}
			else
			{
				$template = 'index';
			}
		}
		
		if ($template_group == '' && $show_default == FALSE && $this->EE->config->item('site_404') != '')
		{
			$treq = $this->EE->config->item('site_404');
			
			$x = explode("/", $treq);

			if (isset($x[0]) AND isset($x[1]))
			{
				$this->EE->output->out_type = '404';
				$this->template_type = '404';
				
				$template_group_404 = $this->EE->db->escape_str($x[0]);
				$template_404 = $this->EE->db->escape_str($x[1]);
				
				$sql_404 = " AND exp_template_groups.group_name='".$this->EE->db->escape_str($x[0])."' AND exp_templates.template_name='".$this->EE->db->escape_str($x[1])."'";
			}	
		}
		 
		$sql = "SELECT exp_templates.template_name, 
						exp_templates.template_id, 
						exp_templates.template_data, 
						exp_templates.template_type,
						exp_templates.edit_date,
						exp_templates.save_template_file,
						exp_templates.cache, 
						exp_templates.refresh, 
						exp_templates.no_auth_bounce, 
						exp_templates.enable_http_auth,
						exp_templates.allow_php, 
						exp_templates.php_parse_location,
						exp_templates.hits,
						exp_template_groups.group_name
				FROM	exp_template_groups, exp_templates
				WHERE  exp_template_groups.group_id = exp_templates.group_id
				AND	exp_template_groups.site_id = '".$this->EE->db->escape_str($site_id)."' ";
				
		if ($sql_404 != '')
		{
			$sql .= $sql_404;
		}
		else
		{
			if ($template != '')
			{
				$sql .= " AND exp_templates.template_name = '".$this->EE->db->escape_str($template)."' ";
			}
		
			if ($show_default == TRUE)
			{
				$sql .= "AND exp_template_groups.is_site_default = 'y'";				
			}
			else
			{
				$sql .= "AND exp_template_groups.group_name = '".$this->EE->db->escape_str($template_group)."'";
			}
		}
				
		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
		{
			// is there a file we can automatically create this template from?
			if ($this->EE->config->item('save_tmpl_files') == 'y' && $this->EE->config->item('tmpl_file_basepath') != '')
			{
				$t_group = ($sql_404 != '') ? $template_group_404 : $template_group;
				$t_template = ($sql_404 != '') ? $template_404 : $template;					
				
				if ($this->_create_from_file($t_group, $t_template, TRUE))
				{
					// run the query again, as we just successfully created it
					$query = $this->EE->db->query($sql);
				}
				else
				{
					$this->log_item("Template Not Found");
					return FALSE;
				}
			}
			else
			{
				$this->log_item("Template Not Found");
				return FALSE;
			}
		}
		
		$this->log_item("Template Found");
		
		/** ----------------------------------------------------
		/**  HTTP Authentication
		/** ----------------------------------------------------*/
		
		if ($query->row('enable_http_auth') == 'y')
		{
			$this->log_item("HTTP Authentication in Progress");
		
			$this->EE->db->select('member_group');
			$this->EE->db->where('template_id', $query->row('template_id'));
			$results = $this->EE->db->get('template_no_access');
		
			$not_allowed_groups = array('2', '3', '4');
			
			if ($results->num_rows() > 0)
			{
				foreach($results->result_array() as $row)
				{
					$not_allowed_groups[] = $row['member_group'];
				}
			}		  
		
			if ($this->template_authentication_check_basic($not_allowed_groups) !== TRUE)
			{
				$this->template_authentication_basic();
			}
		}


		/** ----------------------------------------------------
		/**  Is the current user allowed to view this template?
		/** ----------------------------------------------------*/
		
		if ($query->row('enable_http_auth') != 'y' && $query->row('no_auth_bounce')  != '')
		{
			$this->log_item("Determining Template Access Privileges");
		
			$this->EE->db->select('COUNT(*) as count');
			$this->EE->db->where('template_id', $query->row('template_id'));
			$this->EE->db->where('member_group', $this->EE->session->userdata('group_id'));
			$result = $this->EE->db->get('template_no_access');
			
			if ($result->row('count')  > 0)
			{ 
				if ($this->depth > 0)
				{
					return '';
				}
			
				$sql = "SELECT	a.template_id, a.template_data, a.template_name, a.template_type, a.edit_date, a.save_template_file, a.cache, a.refresh, a.hits, a.allow_php, a.php_parse_location, b.group_name
						FROM	exp_templates a, exp_template_groups b
						WHERE	a.group_id = b.group_id
						AND		template_id = '".$this->EE->db->escape_str($query->row('no_auth_bounce') )."'";
		
				$query = $this->EE->db->query($sql);
			}
		}
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		$row = $query->row_array();
		
		/** -----------------------------------------
		/**  Is PHP allowed in this template?
		/** -----------------------------------------*/
		
		if ($row['allow_php'] == 'y' AND $this->EE->config->item('demo_date') == FALSE)
		{
			$this->parse_php = TRUE;
			
			$this->php_parse_location = ($row['php_parse_location'] == 'i') ? 'input' : 'output';
		}
		
		/** -----------------------------------------
		/**  Increment hit counter
		/** -----------------------------------------*/
		
		if (($this->hit_lock == FALSE OR $this->hit_lock_override == TRUE) AND $this->EE->config->item('enable_hit_tracking') != 'n')
		{
			$this->template_hits = $row['hits'] + 1;
			$this->hit_lock = TRUE;
			
			$this->EE->db->update('templates', array('hits' 		=> $this->template_hits),
											   array('template_id'	=> $row['template_id']));
		}
		
        /** -----------------------------------------
        /**  Set template edit date
        /** -----------------------------------------*/

		$this->template_edit_date = $row['edit_date'];
		
		/** -----------------------------------------
		/**  Set template type for our page headers
		/** -----------------------------------------*/
		if ($this->template_type == '')
		{ 
			$this->template_type = $row['template_type'];
			$this->EE->functions->template_type = $row['template_type'];
			
			/** -----------------------------------------
			/**  If JS or CSS request, reset Tracker Cookie
			/** -----------------------------------------*/
			
			if ($this->template_type == 'js' OR $this->template_type == 'css')
			{
				if (count($this->EE->session->tracker) <= 1)
				{
					$this->EE->session->tracker = array();
				}
				else
				{
					$removed = array_shift($this->EE->session->tracker);
				}
				
				$this->EE->functions->set_cookie('tracker', serialize($this->EE->session->tracker), '0'); 
			}
		}
		
		if ($this->depth > 0)
		{
			$this->embed_type = $row['template_type'];
		}
		
		/** -----------------------------------------
		/**  Cache Override
		/** -----------------------------------------*/
		
		// We can manually set certain things not to be cached, like the
		// search template and the member directory after it's updated
		
	 	// Note: I think search caching is OK.
		// $cache_override = array('member' => 'U', 'search' => FALSE);
		
		$cache_override = array('member');

		foreach ($cache_override as $val)
		{
			if (strncmp($this->EE->uri->uri_string, "/{$val}/", strlen($val) + 2) == 0)
			{
				$row['cache'] = 'n';
			}
		}
		
		/** -----------------------------------------
		/**  Retreive cache
		/** -----------------------------------------*/
					  
		$this->cache_hash = md5($site_id.'-'.$template_group.'-'.$template);

		if ($row['cache'] == 'y')
		{
			$cache_contents = $this->fetch_cache_file($this->cache_hash, 'template', array('cache' => 'yes', 'refresh' => $row['refresh']));
			
			if ($this->cache_status == 'CURRENT')
			{
				return $this->convert_xml_declaration($cache_contents);				
			}
		}
		
		/** -----------------------------------------
        /**  Retrieve template file if necessary
        /** -----------------------------------------*/
        
        if ($row['save_template_file'] == 'y')
        {
        	$site_switch = FALSE;
        	
        	if ($this->EE->config->item('site_id') != $site_id)
        	{
        		$site_switch = $this->EE->config->config;
        		
        		if (isset($this->site_prefs_cache[$site_id]))
        		{
        			$this->EE->config->config = $this->site_prefs_cache[$site_id];
        		}
        		else
        		{
        			$this->EE->config->site_prefs('', $site_id);
					$this->site_prefs_cache[$site_id] = $this->EE->config->config;
        		}
        	}

        	if ($this->EE->config->item('save_tmpl_files') == 'y' AND $this->EE->config->item('tmpl_file_basepath') != '')
        	{
				$this->log_item("Retrieving Template from File");
				$this->EE->load->library('api');
				$this->EE->api->instantiate('template_structure');
				
				$basepath = rtrim($this->EE->config->item('tmpl_file_basepath'), '/').'/';
										
				$basepath .= $this->EE->config->item('site_short_name').'/'.$row['group_name'].'.group/'.$row['template_name'].$this->EE->api_template_structure->file_extensions($row['template_type']);
				
				if (file_exists($basepath))
				{
					$row['template_data'] = file_get_contents($basepath);	
				}
			}
			
			if ($site_switch !== FALSE)
			{
				$this->EE->config->config = $site_switch;
			}
        }
		
		// standardize newlines
		$row['template_data'] =  str_replace(array("\r\n", "\r"), "\n", $row['template_data']);

		return $this->convert_xml_declaration($this->remove_ee_comments($row['template_data']));
	}

	// --------------------------------------------------------------------

	/**
	 * Create From File
	 *
	 * Attempts to create a template group / template from a file
	 *
	 * @access	public
	 * @param	string		template group name
	 * @param	string		template name
	 * @return	bool
	 */
	function _create_from_file($template_group, $template, $db_check = FALSE)
	{
		if ($this->EE->config->item('save_tmpl_files') != 'y' OR $this->EE->config->item('tmpl_file_basepath') == '')
		{
			return FALSE;
		}
		
		$template = ($template == '') ? 'index' : $template;
		
		if ($db_check)
		{
			$this->EE->db->from('templates');
			$this->EE->db->join('template_groups', 'templates.group_id = template_groups.group_id', 'left');
			$this->EE->db->where('group_name', $template_group);			
			$this->EE->db->where('template_name', $template);
			$valid_count =  $this->EE->db->count_all_results();
			
			// We found a valid template!  Er- could this loop?  Better just return FALSE
			if ($valid_count > 0)
			{
				return FALSE;
			}
		}

		$this->EE->load->library('api');
		$this->EE->api->instantiate('template_structure');
		$this->EE->load->model('template_model');
		
		$basepath = $this->EE->config->slash_item('tmpl_file_basepath').$this->EE->config->item('site_short_name').'/'.$template_group.'.group';

		if ( ! is_dir($basepath))
		{
			return FALSE;
		}

		$filename = FALSE;
		
		// Note- we should add the extension before checking.

		foreach ($this->EE->api_template_structure->file_extensions as $type => $temp_ext)
		{
			if (file_exists($basepath.'/'.$template.$temp_ext))
			{
				// found it with an extension
				$filename = $template.$temp_ext;
				$ext = $temp_ext;
				$template_type = $type;
				break;
			}					
		}

		// did we find anything?
		if ($filename === FALSE)
		{
			return FALSE;			
		}
		
		if ( ! $this->EE->api->is_url_safe($template))
		{
			// bail out
			return FALSE;
		}
		
		$this->EE->db->select('group_id');
		$this->EE->db->where('group_name', $template_group);
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('template_groups');

		if ($query->num_rows() != 0)
		{
			$group_id = $query->row('group_id');
		}
		else
		{
			// we have a new group to create!
			if ( ! $this->EE->api->is_url_safe($template_group))
			{
				// bail out
				return FALSE;
			}

			if (in_array($template_group, $this->EE->api_template_structure->reserved_names))
			{
				// bail out
				return FALSE;
			}
			
			$data = array(
							'group_name'		=> $template_group,
							'group_order'		=> $this->EE->db->count_all('template_groups') + 1,
							'is_site_default'	=> 'n',
							'site_id'			=> $this->EE->config->item('site_id')
						);
			
			$group_id = $this->EE->template_model->create_group($data);
		}

		$data = array(
						'group_id'				=> $group_id,
						'template_name'			=> $template,
						'template_type'			=> $template_type,
						'template_data'			=> file_get_contents($basepath.'/'.$filename),
						'edit_date'				=> $this->EE->localize->now,
						'save_template_file'	=> 'y',
						'last_author_id'		=> '1',	// assume a super admin
						'site_id'				=> $this->EE->config->item('site_id')
					 );

		$this->EE->template_model->create_template($data);
		
		// Clear db cache or it will create a new template record each page load!
		$this->EE->functions->clear_caching('db');

		return TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * No Results
	 *
	 * If a tag/class has no results to show, it can call this method.  Any no_results variable in
	 * the tag will be followed.  May be 404 page, content, or even a redirect.
	 *
	 * @access	public
	 * @return	void
	 */
	
	function no_results()
	{
		if ( ! preg_match("/".LD."redirect\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $this->no_results, $match))
		{
			$this->log_item("Returning No Results Content");
			return $this->no_results;
		}
		else
		{
			$this->log_item("Processing No Results Redirect");
			
			if ($match[2] == "404")
			{
				$template = explode('/', $this->EE->config->item('site_404'));

				if (isset($template[1]))
				{
					$this->log_item('Processing "'.$template[0].'/'.$template[1].'" Template as 404 Page');
					$this->output->out_type = "404";
					$this->template_type = "404";
					$this->fetch_and_parse($template[0], $template[1]);
					$this->cease_processing = TRUE;
				}
				else
				{
					$this->log_item('404 redirect requested, but no 404 page is specified in the Global Template Preferences');
					return $this->no_results;
				}
			}
			else
			{
				return $this->EE->functions->redirect($this->EE->functions->create_url($this->EE->functions->extract_path("=".$match[2])));
			}
		}
	}


	// --------------------------------------------------------------------
	
	/**
	 * Make XML Declaration Safe
	 *
	 * Takes any XML declaration in the string  and makes sure it is not interpreted as PHP during
	 * the processing of the template.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	
	// This fixes a parsing error when PHP is used in RSS templates

	function convert_xml_declaration($str)
	{
		if (strpos($str, '<?xml') === FALSE) return $str;
		
		return preg_replace("/\<\?xml(.+?)\?\>/", "<XXML\\1/XXML>", $str);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Restore XML Declaration
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	
	function restore_xml_declaration($str)
	{		
		if (strpos($str, '<XXML') === FALSE) return $str;
		
		return preg_replace("/\<XXML(.+?)\/XXML\>/", "<?xml\\1?".">", $str); // <?
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Remove all EE Code Comment Strings
	 *
	 * EE Templates have a special EE Code Comments for site designer notes and are removed prior
	 * to Template processing.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	 
	function remove_ee_comments($str)
	{	
		if (strpos($str, '{!--') === FALSE) return $str;
		
		return preg_replace("/\{!--.*?--\}/s", '', $str);
	}
	

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Add-ons
	 *
	 * Gathers available modules and plugins
	 *
	 * @access	public
	 * @return	void
	 */
	function fetch_addons()
	{
		$this->EE->load->helper('file');
		$this->EE->load->helper('directory');
		$ext_len = strlen(EXT);
		$pattern = 'bas'.'e'.'6'.'4_d'.'ecode';
		
		// first get first party modules
		if (($map = directory_map(PATH_MOD, TRUE)) !== FALSE)
		{
			foreach ($map as $file)
			{
				if (strpos($file, '.') === FALSE)
				{
					eval($pattern('dW5zZXQoJG1vZHVsZSk7aWYgKElTX0ZSRUVMQU5DRVIgJiYgaW5fYXJyYXkoJGZpbGUsIGFycmF5KCdtZW1iZXInLCAnZm9ydW0nLCAnd2lraScpKSl7JG1vZHVsZT1UUlVFO30='));
					if (isset($module))
					{
						continue;
					}
					$this->modules[] = $file;
				}
			}
		}

		// now first party plugins
		if (($map = directory_map(PATH_PI, TRUE)) !== FALSE)
		{
			foreach ($map as $file)
			{
				if (strncasecmp($file, 'pi.', 3) == 0 && substr($file, -$ext_len) == EXT && strlen($file) > strlen('pi.'.EXT)
					&& in_array(substr($file, 3, -$ext_len), $this->EE->core->native_plugins))
				{							
					$this->plugins[] = substr($file, 3, -$ext_len);
				}				
			}
		}


		// now third party add-ons, which are arranged in "packages"
		// only catch files that match the package name, as other files are merely assets
		if (($map = directory_map(PATH_THIRD, 2)) !== FALSE)
		{
			foreach ($map as $pkg_name => $files)
			{
				if ( ! is_array($files))
				{
					$files = array($files);
				}
				
				foreach ($files as $file)
				{
					if (is_array($file))
					{
						// we're only interested in the top level files for the addon
						continue;
					}
					
					// we gots a module?
					if (strncasecmp($file, 'mod.', 4) == 0 && substr($file, -$ext_len) == EXT && strlen($file) > strlen('mod.'.EXT))
					{
						$file = substr($file, 4, -$ext_len);

						if ($file == $pkg_name)
						{
							$this->modules[] = $file;
						}
					}
					// how abouts a plugin?
					elseif (strncasecmp($file, 'pi.', 3) == 0 && substr($file, -$ext_len) == EXT && strlen($file) > strlen('pi.'.EXT))
					{							
						$file = substr($file, 3, -$ext_len);

						if ($file == $pkg_name)
						{
							$this->plugins[] = $file;
						}
					}					
				}
			}
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Parse Globals
	 *
	 * The syntax is generally: {global:variable_name}
	 *
	 * Parses global variables like the currently logged in member's information, system variables,
	 * paths, action IDs, CAPTCHAs.  Typically stuff that should only be done after caching to prevent
	 * any manner of changes in the system or who is viewing the page to affect the display.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	
	function parse_globals($str)
	{
		$charset 	= '';
		$lang		= '';
		$user_vars	= array('member_id', 'group_id', 'group_description', 'group_title', 'member_group', 'username', 'screen_name', 'email', 'ip_address', 'location', 'total_entries', 'total_comments', 'private_messages', 'total_forum_posts', 'total_forum_topics', 'total_forum_replies');			

        /** --------------------------------------------------
        /**  Redirect - if we have one of these, no need to go further
        /** --------------------------------------------------*/
     	
		if (strpos($str, LD.'redirect') !== FALSE)
		{
			if (preg_match("/".LD."redirect\s*=\s*(\042|\047)([^\\1]*?)\\1".RD."/si", $str, $match))
			{
				if ($match['2'] == "404")
				{
					$template = explode('/', $this->EE->config->item('site_404'));

					if (isset($template['1']))
					{
						$this->log_item('Processing "'.$template['0'].'/'.$template['1'].'" Template as 404 Page');
						$this->template_type = "404";
						$this->fetch_and_parse($template['0'], $template['1']);
						$this->cease_processing = TRUE;
						// the resulting template will not have globals parsed unless we do this
						return $this->parse_globals($this->final_template);
					}
					else
					{
						$this->log_item('404 redirect requested, but no 404 page is specified in the Global Template Preferences');
						return $this->_404();
					}
				}
				else
				{
					// Functions::redirect() exit;s on its own
					$this->EE->functions->redirect($this->EE->functions->create_url($this->EE->functions->extract_path("=".$match['2'])));
				}
			}
		}
						
		/** --------------------------------------------------
		/**  Restore XML declaration if it was encoded
		/** --------------------------------------------------*/
		
		$str = $this->restore_xml_declaration($str);

		/** --------------------------------------------------
		/**  Parse User-defined Global Variables first so that
		/**  they can use other standard globals
		/** --------------------------------------------------*/
	 	
		$this->EE->db->select('variable_name, variable_data');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
		$query = $this->EE->db->get('global_variables');
			
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$str = str_replace(LD.$row['variable_name'].RD, $row['variable_data'], $str); 
			}
		}
		
		/** --------------------------------------------------
		/**  {hits}
		/** --------------------------------------------------*/
		$str = str_replace(LD.'hits'.RD, $this->template_hits, $str);  
		
		/** --------------------------------------------------
		/**  {ip_address} and {ip_hostname}
		/** --------------------------------------------------*/
		
		$str = str_replace(LD.'ip_address'.RD, $this->EE->input->ip_address(), $str); 
		 
		// Turns out gethostbyaddr() is WAY SLOW on many systems so I'm killing it.		 
		// $str = str_replace(LD.'ip_hostname'.RD, @gethostbyaddr($this->EE->input->ip_address()), $str); 
		
		$str = str_replace(LD.'ip_hostname'.RD, $this->EE->input->ip_address(), $str); 
								
		/** --------------------------------------------------
		/**  {homepage}
		/** --------------------------------------------------*/
		
		$str = str_replace(LD.'homepage'.RD, $this->EE->functions->fetch_site_index(), $str); 

		/** --------------------------------------------------
		/**  {cp_url}
		/** --------------------------------------------------*/
	
		if ($this->EE->session->access_cp === TRUE)
		{
			$str = str_replace(LD.'cp_url'.RD, $this->EE->config->item('cp_url'), $str);
		}
		else
		{
			$str = str_replace(LD.'cp_url'.RD, '', $str);
		}

		/** --------------------------------------------------
		/**  {site_name} {site_url} {site_index}
		/** --------------------------------------------------*/
		
		$str = str_replace(LD.'site_name'.RD, stripslashes($this->EE->config->item('site_name')), $str);
		$str = str_replace(LD.'site_url'.RD, stripslashes($this->EE->config->item('site_url')), $str);
		$str = str_replace(LD.'site_index'.RD, stripslashes($this->EE->config->item('site_index')), $str);
		$str = str_replace(LD.'webmaster_email'.RD, stripslashes($this->EE->config->item('webmaster_email')), $str);

		/** --------------------------------------------------
		/**  Stylesheet variable: {stylesheet=group/template}
		/** --------------------------------------------------*/
		
		if (strpos($str, 'stylesheet=') !== FALSE && preg_match_all("/".LD."\s*stylesheet=[\042\047]?(.*?)[\042\047]?".RD."/", $str, $css_matches))
        {
        	$css_versions = array();
        
        	if ($this->EE->config->item('send_headers') == 'y')
        	{
        		$sql = "SELECT t.template_name, tg.group_name, t.edit_date, t.save_template_file FROM exp_templates t, exp_template_groups tg
        				WHERE  t.group_id = tg.group_id
        				AND    t.template_type = 'css'
        				AND    t.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'";
        	
        		foreach($css_matches[1] as $css_match)
        		{
        			$ex = explode('/', $css_match, 2);
        			
        			if (isset($ex[1]))
        			{
        				$css_parts[] = "(t.template_name = '".$this->EE->db->escape_str($ex[1])."' AND tg.group_name = '".$this->EE->db->escape_str($ex[0])."')";
        			}
        		}
        		
        		$css_query = ( ! isset($css_parts)) ? $this->EE->db->query($sql) : $this->EE->db->query($sql.' AND ('.implode(' OR ', $css_parts) .')');
        		
        		if ($css_query->num_rows() > 0)
        		{
        			foreach($css_query->result_array() as $row)
        			{
						$css_versions[$row['group_name'].'/'.$row['template_name']] = $row['edit_date'];
	
						if ($this->EE->config->item('save_tmpl_files') == 'y' AND $this->EE->config->item('tmpl_file_basepath') != '' AND $row['save_template_file'] == 'y')
						{
							$basepath = $this->EE->config->slash_item('tmpl_file_basepath').$this->EE->config->item('site_short_name').'/';
							$basepath .= $row['group_name'].'.group/'.$row['template_name'].'.css';
							
							if (is_file($basepath))
							{
								$css_versions[$row['group_name'].'/'.$row['template_name']] = filemtime($basepath);
							}
						}
        			}
        		}
        	}
        	
        	for($ci=0, $cs=count($css_matches[0]); $ci < $cs; ++$ci)
        	{
        		$str = str_replace($css_matches[0][$ci], $this->EE->functions->fetch_site_index().QUERY_MARKER.'css='.$css_matches[1][$ci].(isset($css_versions[$css_matches[1][$ci]]) ? '.v.'.$css_versions[$css_matches[1][$ci]] : ''), $str);
        	}
        
        	unset($css_matches);
        	unset($css_versions);
        }
		  
		/** --------------------------------------------------
		/**  Email encode: {encode="you@yoursite.com" title="click Me"}
		/** --------------------------------------------------*/
		
		if (strpos($str, LD.'encode=') !== FALSE)
		{
			if ($this->encode_email == TRUE)
			{
				if (preg_match_all("/".LD."encode=(.+?)".RD."/i", $str, $matches))
				{	
					for ($j = 0; $j < count($matches[0]); $j++)
					{					
						$str = preg_replace('/'.preg_quote($matches['0'][$j], '/').'/', $this->EE->functions->encode_email($matches[1][$j]), $str, 1);
					}
				}  		
			}
			else
			{
				/* -------------------------------------------
				/*	Hidden Configuration Variable
				/*	- encode_removed_text => Text to display if there is an {encode=""} 
					tag but emails are not to be encoded
				/* -------------------------------------------*/
			
				$str = preg_replace("/".LD."\s*encode=(.+?)".RD."/", 
									($this->EE->config->item('encode_removed_text') !== FALSE) ? $this->EE->config->item('encode_removed_text') : '', 
									$str);
			}
		}
		
		/** --------------------------------------------------
		/**  Path variable: {path=group/template}
		/** --------------------------------------------------*/
		
		if (strpos($str, 'path=') !== FALSE)
		{
			$str = preg_replace_callback("/".LD."\s*path=(.*?)".RD."/", array(&$this->EE->functions, 'create_url'), $str);
		}
		
		/** --------------------------------------------------
		/**  Debug mode: {debug_mode}
		/** --------------------------------------------------*/
		
		$str = str_replace(LD.'debug_mode'.RD, ($this->EE->config->item('debug') > 0) ? $this->EE->lang->line('on') : $this->EE->lang->line('off'), $str);
				
		/** --------------------------------------------------
		/**  GZip mode: {gzip_mode}
		/** --------------------------------------------------*/
		$str = str_replace(LD.'gzip_mode'.RD, ($this->EE->config->item('gzip_output') == 'y') ? $this->EE->lang->line('enabled') : $this->EE->lang->line('disabled'), $str);
				
		/** --------------------------------------------------
		/**  App version: {version}
		/** --------------------------------------------------*/
		
		$str = str_replace(LD.'app_version'.RD, APP_VER, $str); 
		$str = str_replace(LD.'version'.RD, APP_VER, $str); 
		 
		/** --------------------------------------------------
		/**  App version: {build}
		/** --------------------------------------------------*/
		
		$str = str_replace(LD.'app_build'.RD, APP_BUILD, $str); 
		$str = str_replace(LD.'build'.RD, APP_BUILD, $str);

		/** --------------------------------------------------
		/**  {charset} and {lang}
		/** --------------------------------------------------*/
		
		$str = str_replace(LD.'charset'.RD, $this->EE->config->item('output_charset'), $str); 
		$str = str_replace(LD.'lang'.RD, $this->EE->config->item('xml_lang'), $str);

		/** --------------------------------------------------
		/**  {doc_url}
		/** --------------------------------------------------*/

		$str = str_replace(LD.'doc_url'.RD, $this->EE->config->item('doc_url'), $str);
		
		/** --------------------------------------------------
		/**  {member_profile_link}
		/** --------------------------------------------------*/
		if ($this->EE->session->userdata('member_id') != 0)
		{
			$name = ($this->EE->session->userdata['screen_name'] == '') ? $this->EE->session->userdata['username'] : $this->EE->session->userdata['screen_name'];
			
			$path = "<a href='".$this->EE->functions->create_url('/member/'.$this->EE->session->userdata('member_id'))."'>".$name."</a>";
			
			$str = str_replace(LD.'member_profile_link'.RD, $path, $str);
		}
		else
		{
			$str = str_replace(LD.'member_profile_link'.RD, '', $str);
		}
		
		/** -----------------------------------
		/**  Fetch CAPTCHA
		/** -----------------------------------*/
		
		if (strpos($str, "{captcha}") !== FALSE)
		{
			$str = str_replace("{captcha}", $this->EE->functions->create_captcha(), $str);
		}		
					
		/** -----------------------------------
		/**  Add security hashes to forms
		/** -----------------------------------*/
		
		// We do this here to keep the security hashes from being cached
		
		$str = $this->EE->functions->add_form_security_hash($str);
		
		/** -----------------------------------
		/**  Add Action IDs form forms and links
		/** -----------------------------------*/
		
		$str = $this->EE->functions->insert_action_ids($str);
		
		/** -----------------------------------
		/**  Parse non-cachable variables
		/** -----------------------------------*/
		
		$this->EE->session->userdata['member_group'] = $this->EE->session->userdata['group_id'];
	
		foreach ($user_vars as $val)
		{
			if (isset($this->EE->session->userdata[$val]) AND ($val == 'group_description' OR strval($this->EE->session->userdata[$val]) != ''))
			{
				$str = str_replace(LD.$val.RD, $this->EE->session->userdata[$val], $str);				 
				$str = str_replace('{out_'.$val.'}', $this->EE->session->userdata[$val], $str);
				$str = str_replace('{global->'.$val.'}', $this->EE->session->userdata[$val], $str);
				$str = str_replace('{logged_in_'.$val.'}', $this->EE->session->userdata[$val], $str);
			}
		}
		
		// and once again just in case global vars introduce EE comments
		return $this->remove_ee_comments($str);
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Parse Uncachaable Forms
	 *
	 * Parses and Process forms that cannot be stored in a cache file.  Probably one of the most
	 * tedious parts of EE's Template parser for a while there in 2004...
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	
	function parse_nocache($str)
	{
		if (strpos($str, '{NOCACHE') === FALSE)
		{
			return $str;
		}
		
		/** -----------------------------------
		/**  Generate Comment Form if needed
		/** -----------------------------------*/
		
		// In order for the comment form not to cache the "save info"
		// data we need to generate dynamically if necessary
		
		if (preg_match_all("#{NOCACHE_(\S+)_FORM=\"(.*?)\"}(.+?){/NOCACHE_FORM}#s", $str, $match))
		{
			$this->EE->load->library('security');
			
			for($i=0, $s=count($match[0]); $i < $s; $i++)
			{
				$class = $this->EE->security->sanitize_filename(strtolower($match[1][$i]));
		
				if ( ! class_exists($class))
				{
					require PATH_MOD.$class.'/mod.'.$class.EXT;
				}
						
				$this->tagdata = $match[3][$i];

				$vars = $this->EE->functions->assign_variables($match[3][$i], '/');			
				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];
				
				$this->tagparams = $this->EE->functions->assign_parameters($match[2][$i]);
				
				$this->var_cond = $this->EE->functions->assign_conditional_variables($match[3][$i], '/', LD, RD);
				
				// Assign sites for the tag
				$this->_fetch_site_ids();

				// Assign Form ID/Classes
				if (isset($this->tag_data[$i]))
				{
					$this->tag_data[$i] = $this->_assign_form_params($this->tag_data[$i]);					
				}

				if ($class == 'comment')
				{
					$str = str_replace($match[0][$i], Comment::form(TRUE, $this->EE->functions->cached_captcha), $str);	
				}
				
				$str = str_replace('{PREVIEW_TEMPLATE}', $match[2][$i], $str);	
			}
		}
		
		/** -----------------------------------
		/**  Generate Stand-alone Publish form
		/** -----------------------------------*/
		if (preg_match_all("#{{NOCACHE_CHANNEL_FORM(.*?)}}(.+?){{/NOCACHE_FORM}}#s", $str, $match))
		{
			for($i=0, $s=count($match[0]); $i < $s; $i++)
			{
				if ( ! class_exists('Channel'))
				{
					require PATH_MOD.'channel/mod.channel'.EXT;
				}
				
				$this->tagdata = $match[2][$i];
				
				$vars = $this->EE->functions->assign_variables($match[2][$i], '/');
				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];
				
				$this->tagparams = $this->EE->functions->assign_parameters($match[1][$i]);
				
				// Assign sites for the tag
				$this->_fetch_site_ids();

				// Assign Form ID/Classes
				if (isset($this->tag_data[$i]))
				{
					$this->tag_data[$i] = $this->_assign_form_params($this->tag_data[$i]);					
				}

				$XX = new Channel();
				$str = str_replace($match[0][$i], $XX->entry_form(TRUE, $this->EE->functions->cached_captcha), $str);
				$str = str_replace('{PREVIEW_TEMPLATE}', (isset($_POST['PRV'])) ? $_POST['PRV'] : $this->fetch_param('preview'), $str);	
			}
		}

		return $str;
	
	}


	// --------------------------------------------------------------------
	
	/**
	 * Process Advanced Conditionals
	 *
	 * The syntax is generally: {if whatever = ""}Dude{if:elseif something != ""}Yo{if:else}
	 *
	 * The final processing of Advanced Conditionals.  Takes all of the member variables and uncachable
	 * variables and preps the conditionals with them.  Then, it converts the conditionals to PHP so that
	 * PHP can do all of the really heavy lifting for us.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	 
	function advanced_conditionals($str)
	{
		if (stristr($str, LD.'if') === FALSE)
		{
			return $str;			
		}
			
		/* ---------------------------------
		/*	Hidden Configuration Variables
		/*  - protect_javascript => Prevents advanced conditional parser from processing anything in <script> tags
		/* ---------------------------------*/
			
		if ($this->EE->config->item('protect_javascript') == 'n')
		{
			$this->protect_javascript = FALSE;
		}
		
		$user_vars	= array('member_id', 'group_id', 'group_description', 'group_title', 'username', 'screen_name', 
							'email', 'ip_address', 'location', 'total_entries', 
							'total_comments', 'private_messages', 'total_forum_posts', 'total_forum_topics', 'total_forum_replies');
		
		for($i=0,$s=count($user_vars), $data = array(); $i < $s; ++$i)
		{
			$data[$user_vars[$i]] = $this->EE->session->userdata[$user_vars[$i]];
			$data['logged_in_'.$user_vars[$i]] = $this->EE->session->userdata[$user_vars[$i]];
		}
		
		// Define an alternate variable for {group_id} since some tags use
		// it natively, causing it to be unavailable as a global
		
		$data['member_group'] = $data['logged_in_member_group'] = $this->EE->session->userdata['group_id'];
		
		// Logged in and logged out variables
		$data['logged_in'] = ($this->EE->session->userdata['member_id'] == 0) ? 'FALSE' : 'TRUE';
		$data['logged_out'] = ($this->EE->session->userdata['member_id'] != 0) ? 'FALSE' : 'TRUE';
		
		// current time
		$data['current_time'] = $this->EE->localize->now;
		
		/** ------------------------------------
		/**  Member Group in_group('1') function, Super Secret!  Shhhhh!
		/** ------------------------------------*/
		
		if (preg_match_all("/in_group\(([^\)]+)\)/", $str, $matches))
		{
			$groups = (is_array($this->EE->session->userdata['group_id'])) ? $this->EE->session->userdata['group_id'] : array($this->EE->session->userdata['group_id']);
		
			for($i=0, $s=count($matches[0]); $i < $s; ++$i)
			{
				$check = explode('|', str_replace(array('"', "'"), '', $matches[1][$i]));
				
				$str = str_replace($matches[0][$i], (count(array_intersect($check, $groups)) > 0) ? 'TRUE' : 'FALSE', $str);
			}
		}
		
		/** ------------------------------------
		/**  Final Prep, Safety On
		/** ------------------------------------*/
		$str = $this->EE->functions->prep_conditionals($str, array_merge($this->segment_vars, $this->embed_vars, $this->EE->config->_global_vars, $data), 'y');
				
		/** ------------------------------------
		/**  Protect Already Existing Unparsed PHP
		/** ------------------------------------*/
		
		$opener = '90Parse89Me34Not18Open';
		$closer = '90Parse89Me34Not18Close';
		
		$str = str_replace(array('<?', '?'.'>'), 
							array($opener.'?', '?'.$closer), 
							$str);
		
		/** ------------------------------------
		/**  Protect <script> tags
		/** ------------------------------------*/

		$protected = array();
		$front_protect = '89Protect17';
		$back_protect  = '21Me01Please47';
		
		if ($this->protect_javascript !== FALSE && 
			stristr($str, '<script') && 
			preg_match_all("/<script.*?".">.*?<\/script>/is", $str, $matches))
		{
			for($i=0, $s=count($matches[0]); $i < $s; ++$i)
			{
				$protected[$front_protect.$i.$back_protect] = $matches[0][$i];
			}
			
			$str = str_replace(array_values($protected), array_keys($protected), $str);
		}

		/** ------------------------------------
		/**  Convert EE Conditionals to PHP 
		/** ------------------------------------*/
		
		$str = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $str);
		
		if (strpos($str, LD.'if') !== FALSE)
		{
			$str = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s+(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $str);
		}

		$str = $this->parse_template_php($str);
		
		/** ------------------------------------
		/**  Unprotect <script> tags
		/** ------------------------------------*/
		
		if (count($protected) > 0)
		{
			$str = str_replace(array_keys($protected), array_values($protected), $str);
		}
		
		/** ------------------------------------
		/**  Unprotect Already Existing Unparsed PHP
		/** ------------------------------------*/
		
		$str = str_replace(array($opener.'?', '?'.$closer), 
							array('<'.'?', '?'.'>'), 
							$str);
		
		return $str;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Parse Simple Segment Conditionals
	 *
	 * Back before Advanced Conditionals many people put embedded templates and preload_replace="" 
	 * variables in segment conditionals to control what subpages were included and the values of
	 * many tag parameters.   Since Advanced Conditionals are processed far later in Template parsing
	 * than that usage was required, we kept some separate processing in existence for the processing
	 * of "simple" segment conditionals.  Only one variable, no elseif or else.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	 
	function parse_simple_segment_conditionals($str)
	{
		if ( ! preg_match("/".LD."if\s+segment_.+".RD."/", $str))
		{
			return $str;
		}
		
		$this->var_cond = $this->EE->functions->assign_conditional_variables($str);

		foreach ($this->var_cond as $val)
		{
			// Make sure this is for a segment conditional
			// And that this is not an advanced conditional
			
				if ( ! preg_match('/^segment_\d+$/i', $val['3']) OR
				strpos($val[2], 'if:else') !== FALSE OR
				strpos($val[0], 'if:else') !== FALSE OR
				count(preg_split("/(\!=|==|<=|>=|<>|<|>|AND|XOR|OR|&&|\|\|)/", $val[0])) > 2)
			{
				continue;	
			}			
			
			$cond = $this->EE->functions->prep_conditional($val[0]);
			
			$lcond	= substr($cond, 0, strpos($cond, ' '));
			$rcond	= substr($cond, strpos($cond, ' '));
			
			if (strpos($rcond, '"') == FALSE && strpos($rcond, "'") === FALSE) continue;
			
			$n = substr($val[3], 8);
			$temp = (isset($this->EE->uri->segments[$n])) ? $this->EE->uri->segments[$n] : '';

			$lcond = str_replace($val[3], "\$temp", $lcond);
			
			if (stristr($rcond, '\|') !== FALSE OR stristr($rcond, '&') !== FALSE)
			{
				$rcond	  = trim($rcond);
				$operator = trim(substr($rcond, 0, strpos($rcond, ' ')));
				$check	  = trim(substr($rcond, strpos($rcond, ' ')));
			
				$quote = substr($check, 0, 1);
				
				if (stristr($rcond, '\|') !== FALSE)
				{
					$array =  explode('\|', str_replace($quote, '', $check));
					$break_operator = ' OR ';
				}
				else
				{
					$array =  explode('&', str_replace($quote, '', $check));
					$break_operator = ' && ';
				}
				
				$rcond  = $operator.' '.$quote;
				
				$rcond .= implode($quote.$break_operator.$lcond.' '.$operator.' '.$quote, $array).$quote;
			}
			
			$cond = $lcond.' '.$rcond;
			  
			$cond = str_replace("\|", "|", $cond);

			eval("\$result = (".$cond.");");
								
			if ($result)
			{
				$str = str_replace($val[1], $val[2], $str);				 
			}
			else
			{
				$str = str_replace($val[1], '', $str);				 
			}	
		}		

		return $str;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Parse Simple Conditionals
	 *
	 * Used for processing global_vars and embed and segment conditionals that need to occur far
	 * sooner than Advanced Conditionals.  These conditionals are only one variable and have no 
	 * {if:elseif} or {if:else} control structures.
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @return	string
	 */

	function simple_conditionals($str, $vars = array())
	{
		if (count($vars) == 0 OR ! stristr($str, LD.'if'))
		{
			return $str;
		}
	
		$this->var_cond = $this->EE->functions->assign_conditional_variables($str);
		
		if (count($this->var_cond) == 0)
		{
			return $str;
		}

		foreach ($this->var_cond as $val)
		{
			// Make sure there is such a $global_var
			// And that this is not an advanced conditional
			
			if ( ! isset($vars[$val[3]]) OR 
				strpos($val[2], 'if:else') !== FALSE OR
				strpos($val[0], 'if:else') !== FALSE OR
				count(preg_split("/(\!=|==|<=|>=|<>|<|>|AND|XOR|OR|&&|\|\|)/", $val[0])) > 2)
			{
				continue;	
			}
			
			$cond = $this->EE->functions->prep_conditional($val[0]);
			
			$lcond	= substr($cond, 0, strpos($cond, ' '));
			$rcond	= substr($cond, strpos($cond, ' '));
			
			if (strpos($rcond, '"') == FALSE && strpos($rcond, "'") === FALSE) continue;
			
			$temp = $vars[$val[3]];

			$lcond = str_replace($val[3], "\$temp", $lcond);
			
			if (stristr($rcond, '\|') !== FALSE OR stristr($rcond, '&') !== FALSE)
			{
				$rcond	  = trim($rcond);
				$operator = trim(substr($rcond, 0, strpos($rcond, ' ')));
				$check	  = trim(substr($rcond, strpos($rcond, ' ')));
			
				$quote = substr($check, 0, 1);
				
				if (stristr($rcond, '\|') !== FALSE)
				{
					$array =  explode('\|', str_replace($quote, '', $check));
					$break_operator = ' OR ';
				}
				else
				{
					$array =  explode('&', str_replace($quote, '', $check));
					$break_operator = ' && ';
				}
				
				$rcond  = $operator.' '.$quote;
				
				$rcond .= implode($quote.$break_operator.$lcond.' '.$operator.' '.$quote, $array).$quote;
			}
			
			$cond = $lcond.' '.$rcond;
			  
			$cond = str_replace("\|", "|", $cond);

			eval("\$result = (".$cond.");");
								
			if ($result)
			{
				$str = str_replace($val[1], $val[2], $str);				 
			}
			else
			{
				$str = str_replace($val[1], '', $str);				 
			}	
		}		
		
		return $str;
		
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Log Item for Template Processing Log
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	
	function log_item($str)
	{
		if ($this->debugging !== TRUE)
		{
			return;
		}
	
		if ($this->depth > 0)
		{
			$str = str_repeat('&nbsp;', $this->depth * 5).$str;
		}
		
		if (phpversion() < 5)
		{
			list($usec, $sec) = explode(" ", microtime());
			$time = ((float)$usec + (float)$sec) - $this->start_microtime;
		}
		else
		{
			$time = microtime(TRUE)-$this->start_microtime;
		}
		
		$this->log[] = '('.number_format($time, 6).') '.$str;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Basic HTTP Authentication for Templates
	 *
	 * @access	public
	 * @return	header
	 */
	
	function template_authentication_basic()
	{
		@header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
		$this->EE->output->set_status_header(401);
		@header("Date: ".gmdate("D, d M Y H:i:s")." GMT");
		exit("HTTP/1.0 401 Unauthorized");
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * HTTP Authentication Validation
	 *
	 * Takes the username/password from the HTTP Authentication and validates it against the
	 * member database and see if this member's member group has access to the template.
	 *
	 * @access	public
	 * @param	array
	 * @return	header
	 */
	
	function template_authentication_check_basic($not_allowed_groups = array())
	{
		if ( ! in_array('2', $not_allowed_groups))
		{
			$not_allowed_groups[] = 2;
			$not_allowed_groups[] = 3;
			$not_allowed_groups[] = 4;
		}
		
		/** ----------------------------------
		/**  Find Username, Please
		/** ----------------------------------*/
		if ( ! empty($_SERVER) && isset($_SERVER['PHP_AUTH_USER']))
		{
			$user = $_SERVER['PHP_AUTH_USER'];
		}
		elseif ( ! empty($_ENV) && isset($_ENV['REMOTE_USER']))
		{
			$user = $_ENV['REMOTE_USER'];
		}
		elseif ( @getenv('REMOTE_USER'))
		{
			$user = getenv('REMOTE_USER');
		}
		elseif ( ! empty($_ENV) && isset($_ENV['AUTH_USER']))
		{
			$user = $_ENV['AUTH_USER'];
		}
		elseif ( @getenv('AUTH_USER'))
		{
			$user = getenv('AUTH_USER');
		}
		
		/** ----------------------------------
		/**  Find Password, Please
		/** ----------------------------------*/
		
		if ( ! empty($_SERVER) && isset($_SERVER['PHP_AUTH_PW']))
		{
			$pass = $_SERVER['PHP_AUTH_PW'];
		}
		elseif ( ! empty($_ENV) && isset($_ENV['REMOTE_PASSWORD']))
		{
			$pass = $_ENV['REMOTE_PASSWORD'];
		}
		elseif ( @getenv('REMOTE_PASSWORD'))
		{
			$pass = getenv('REMOTE_PASSWORD');
		}
		elseif ( ! empty($_ENV) && isset($_ENV['AUTH_PASSWORD']))
		{
			$pass = $_ENV['AUTH_PASSWORD'];
		}
		elseif ( @getenv('AUTH_PASSWORD'))
		{
			$pass = getenv('AUTH_PASSWORD');
		}
		
		/** ----------------------------------
		/**  Authentication for IIS
		/** ----------------------------------*/
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{
			if ( isset($_SERVER['HTTP_AUTHORIZATION']) && substr($_SERVER['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($HTTP_AUTHORIZATION, 6)));
			}
			elseif ( ! empty($_ENV) && isset($_ENV['HTTP_AUTHORIZATION']) && substr($_ENV['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['HTTP_AUTHORIZATION'], 6)));
			}
			elseif (@getenv('HTTP_AUTHORIZATION') && substr(getenv('HTTP_AUTHORIZATION'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('HTTP_AUTHORIZATION'), 6)));
			}
		}
		
		/** ----------------------------------
		/**  Authentication for FastCGI
		/** ----------------------------------*/
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{	
			if ( ! empty($_ENV) && isset($_ENV['Authorization']) && substr($_ENV['Authorization'], 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr($_ENV['Authorization'], 6)));
			}
			elseif (@getenv('Authorization') && substr(getenv('Authorization'), 0, 6) == 'Basic ')
			{
				list($user, $pass) = explode(':', base64_decode(substr(getenv('Authorization'), 6)));
			}
		}
		
		if ( ! isset ($user) OR ! isset($pass) OR (empty($user) && empty($pass)))
		{
			return FALSE;
		}
		
		/** ----------------------------------------
		/**  Check password lockout status
		/** ----------------------------------------*/
		
		if ($this->EE->session->check_password_lockout($user) === TRUE)
		{
			return FALSE;	
		}
		
		/** ----------------------------------
		/**  Validate Username and Password
		/** ----------------------------------*/
		
		$query = $this->EE->db->query("SELECT password, group_id FROM exp_members WHERE username = '".$this->EE->db->escape_str($user)."'");
		
		if ($query->num_rows() == 0)
		{
			$this->EE->session->save_password_lockout($user);
			return FALSE;
		}
		
		if (in_array($query->row('group_id') , $not_allowed_groups))
		{
			return FALSE;
		}
		
		$this->EE->load->helper('security');
		if ($query->row('password')  == do_hash($pass))
		{
			return TRUE;
		}
		
		// just in case it's still in the db as MD5 from an old pMachine or EE 1.x install
		if ($query->row('password')  == do_hash($pass, 'md5'))
		{
			return TRUE;
		}
		else
		{
			$this->EE->session->save_password_lockout($user);
			
			return FALSE;
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 *	Assign Form Params
	 *
	 *	Extract form_class / form_id from tagdata, and assign it to a class property 
	 *	So it can be easily accessed.
	 *
	 *	@access private
	 *	@param 	array
	 *	@return array
	 */
	function _assign_form_params($tag_data)
	{
		$this->form_id 		= '';
		$this->form_class 	= '';

		if ( ! isset($tag_data['params']) OR ! is_array($tag_data['params']))
		{
			return $tag_data;
		}
		
		if (array_key_exists('form_id', $tag_data['params']))
		{
			$this->form_id = $tag_data['params']['form_id'];
		}
		
		if (array_key_exists('form_class', $tag_data['params']))
		{
			$this->form_class = $tag_data['params']['form_class'];
		}		

		return $tag_data;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch Site IDs for this Installation
	 *
	 * As ExpressionEngine can include data from other Sites in its installation, we need to validate
	 * these parameters and load the data from the correct site.  We put it into a class variable
	 * so that it only has to happen once during a page request
	 *
	 * @access	private
	 */
	
	function _fetch_site_ids()
	{
		$this->site_ids = array();
		
		if (isset($this->tagparams['site']))
		{
			if (count($this->sites) == 0 && $this->EE->config->item('multiple_sites_enabled') == 'y' && ! IS_FREELANCER)
			{
				$sites_query = $this->EE->db->query("SELECT site_id, site_name FROM exp_sites ORDER BY site_id");
				
				foreach($sites_query->result_array() as $row)
				{
					$this->sites[$row['site_id']] = $row['site_name'];
				}
			}
			
			if (substr($this->tagparams['site'], 0, 4) == 'not ')
			{
				$sites = array_diff($this->sites, explode('|', substr($this->tagparams['site'], 4)));	
			}
			else
			{
				$sites = array_intersect($this->sites, explode('|', $this->tagparams['site']));
			}
			
			// Let us hear it for the preservation of array keys!
			$this->site_ids = array_flip($sites);
		}
		
		// If no sites were assigned via parameter, then we use the current site's
		// Templates, Channels, and various Site data
		
		if (count($this->site_ids) == 0)
		{
			$this->site_ids[] = $this->EE->config->item('site_id');
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Parse Variables
	 *
	 * Simplifies variable parsing for plugin
	 * and modules developers
	 *
	 * @access	public
	 * @param	string	- the tagdata / text to be parsed
	 * @param	array	- the rows of variables and their data
	 * @return	string
	 */
	function parse_variables($tagdata, $variables)
	{		
		if ($tagdata == '' OR ! is_array($variables) OR empty($variables) OR ! is_array($variables[0]))
		{
			return $tagdata;
		}
		
		// Reset and Match date variables
		$this->date_vars = array();
		$this->_match_date_vars($tagdata);

		// Unfound Variables that We Need Not Parse - Reset
		$this->unfound_vars = array(array()); // nested for depth 0
		
		// Match {switch="foo|bar"} variables			
		$switch = array();

		if (preg_match_all("/".LD."(switch\s*=.+?)".RD."/i", $tagdata, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$sparam = $this->EE->functions->assign_parameters($match[1]);

				if (isset($sparam['switch']))
				{
					$sopt = explode("|", $sparam['switch']);

					$switch[$match[1]] = $sopt;
				}
			}
		}

		// Blast through the array to build our output
		$str = '';
		$count = 0;
		$total_results = count($variables);
		
		while (($row = array_shift($variables)) !== NULL)
		{
			$count++;
			
			// Add {count} variable
			if ( ! isset($row['count']))
			{
				$row['count'] = $count;
			}
			
			// Add {total_results} variable
			if ( ! isset($row['total_results']))
			{
				$row['total_results'] = $total_results;
			}
			
			// Set {switch} variable values
			foreach ($switch as $key => $val)
			{
				$row[$key] = $switch[$key][($count + count($val) -1) % count($val)];
			}
			
			$str .= $this->parse_variables_row($tagdata, $row, FALSE);
		}

		if (($backspace = $this->fetch_param('backspace')) !== FALSE && is_numeric($backspace))
		{
			$str = substr($str, 0, -$backspace);
		}
		
		return $str;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Parse Variables Row
	 *
	 * Handles a "row" of variable data from
	 * the parse_variables() method
	 *
	 * @access	public
	 * @param	string	- the tagdata / text to be parsed
	 * @param	array	- the variables and their data
	 * @param	bool	- coming from parse_variables() or part of set, forces some caching
	 * 
	 * @return	string
	 */
	function parse_variables_row($tagdata, $variables, $solo = TRUE)
	{
		if ($tagdata == '' OR ! is_array($variables) OR empty($variables))
		{
			return $tagdata;
		}
		
		if ($solo === TRUE)
		{
			$this->unfound_vars = array(array()); // nested for depth = 0
		}
		
		// Match date variables if necessary
		if (empty($this->date_vars) && $this->date_vars !== FALSE)
		{
			$this->_match_date_vars($tagdata);
		}
				
		$this->conditional_vars = $variables;

		foreach ($variables as $name => $value)
		{
			if (isset($this->unfound_vars[0][$name])) continue;
			
			if (strpos($tagdata, LD.$name) === FALSE)
			{
				$this->unfound_vars[0][$name] = TRUE;
				continue;
			}
		
			// Pair variables are an array of arrays
			if (is_array($value))
			{
				$tagdata = $this->_parse_var_pair($name, $value, $tagdata, 1);
			}
			else
			{
				$tagdata = $this->_parse_var_single($name, $value, $tagdata);
			}
		}
		
		// Prep conditionals
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $this->conditional_vars);
			
		return $tagdata;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Parse Var Single
	 *
	 * Parses single variables from the parse_variables() method
	 *
	 * @access	public
	 * @param	string	- the variable's name
	 * @param	string	- the variable's value
	 * @param	string	- the text to parse
	 * @return	string
	 */
	function _parse_var_single($name, $value, $string)
	{
		// parse date variables where applicable
		if (isset($this->date_vars[$name]))
		{
			foreach ($this->date_vars[$name] as $dvar => $dval)
			{
				$val = array_shift($dval);
				$string = str_replace(LD.$dvar.RD,
									  str_replace($dval, $this->EE->localize->convert_timestamp($dval, $value, TRUE), $val),
									  $string);
			}
			
			// unformatted dates
			if (strpos($string, LD.$name.RD) !== FALSE)
			{
				$string = str_replace(LD.$name.RD, $value, $string);					
			}
			
			return $string;
		}
		
		// Simple Variable - Find & Replace & Return
		if (is_string($value))
		{
			return str_replace(LD.$name.RD, $value, $string);	
		}
		
		//
		// Complex Paths and Typography Variables
		//
		
		// If the variable's $value is an array where $value[0] is 'path' and $value[1] has either
		// the key 'suffix' or 'url' set, then it is a path
		if (is_array($value) && $value[0] == 'path' && isset($value[1]) && (isset($value[1]['suffix']) OR isset($value[1]['url'])))
		{
			// Um...not sure what to do here, quite yet.
			return $string;
		}
	
		// If the single variable's value is an array, then
		// $value[0] is the content and $value[1] is an array
		// of parameters for the Typography class
		elseif (is_array($value) && count($value) == 2 && is_array($value[1]))
		{			
			$raw_content = $value[0];
			
			$prefs = array();
			
			foreach (array('text_format', 'html_format', 'auto_links', 'allow_img_url', 'convert_curly') as $pref)
			{
				if (isset($value[1][$pref]))
				{
					$prefs[$pref] = $value[1][$pref];
				}
			}
			
			// Instantiate Typography only if necessary
			$this->EE->load->library('typography');
			$this->EE->typography->initialize();
			
			$this->EE->typography->convert_curly = (isset($prefs['convert_curly']) && $prefs['convert_curly'] == 'n') ? FALSE : TRUE;
			
			$value = $this->EE->typography->parse_type($raw_content, $prefs);
		}
		
		if (isset($raw_content))
		{
			$this->conditional_vars[$name] = $raw_content;
		}
		
		return str_replace(LD.$name.RD, $value, $string);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Parse Var Pair
	 *
	 * Parses pair variables from the parse_variables() method
	 *
	 * @access	public
	 * @param	string	- the variable pair's name
	 * @param	array	- the variable pair's single variables
	 * @param	string	- the text to parse
	 * @param	integer	- iteration depth for unfound_vars
	 * @return	string
	 */
	function _parse_var_pair($name, $variables, $string, $depth = 0)
	{		
		if ( ! preg_match("|".LD.$name.'.*?'.RD.'(.*?)'.LD.'/'.$name.RD."|s", $string, $match))
		{
			return $string;
		}

		if (empty($variables[0]))
		{
			return str_replace($match[0], '', $string);
		}
		
		if ( ! isset($this->unfound_vars[$depth]))
		{
			$this->unfound_vars[$depth] = array();
		}
		
		$str = '';

		foreach ($variables as $set)
		{
			$temp = $match[1];

			foreach ($set as $name => $value)
			{
				if (isset($this->unfound_vars[$depth][$name])) continue;
			
				if (strpos($string, LD.$name) === FALSE)
				{
					$this->unfound_vars[$depth][$name] = TRUE;
					continue;
				}
			
				// Pair variables are an array of arrays.
				if (is_array($value) && is_array($value[0]))
				{
					$temp = $this->_parse_var_pair($name, $value, $temp, $depth + 1);
				}
				else
				{
					$temp = $this->_parse_var_single($name, $value, $temp);
				}
			}

			// Prep conditionals
			$temp = $this->EE->functions->prep_conditionals($temp, $set);
			
			$str .= $temp;
		}
		
		return str_replace($match[0], $str, $string);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Match Date Vars
	 *
	 * Finds date variables within tagdata
	 * 	 array structure:
	 *	 [name] => Array
	 *	     (
	 *	         [name format="%m/%d/%y"] => Array
	 *	             (
	 *	                 [0] => %m/%d/%y
	 *	                 [1] => %m
	 *	                 [2] => %d
	 *	                 [3] => %y
	 *	             )
 	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	 
	function _match_date_vars($str)
	{
		if (strpos($str, 'format=') === FALSE) return;
	
		if (preg_match_all("/".LD."([^".RD."]*?)\s+format=[\"'](.*?)[\"']".RD."/s", $str, $matches, PREG_SET_ORDER))
		{
			for ($j = 0, $tot = count($matches); $j < $tot; $j++)
			{
				$matches[$j][0] = str_replace(array(LD,RD), '', $matches[$j][0]);

				$this->date_vars[$matches[$j][1]][$matches[$j][0]] = array_merge(array($matches[$j][2]), $this->EE->localize->fetch_date_params($matches[$j][2]));
			}
		}
		else
		{
			// make sure we don't try to parse date variables again on further calls to parse_variables() or parse_variables_row()
			$this->date_vars = FALSE;
		}
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file Template.php */
/* Location: ./system/expressionengine/libraries/Template.php */