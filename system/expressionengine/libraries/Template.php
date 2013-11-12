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
 * ExpressionEngine Template Parser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
	var $attempted_fetch	=  array();		// Templates attempted to fetch but may have bailed due to recursive embeds
	var $encode_email		=  TRUE;		// Whether to use the email encoder.  This is set automatically
	var $hit_lock_override	=  FALSE;		// Set to TRUE if you want hits tracked on sub-templates
	var $hit_lock			=  FALSE;		// Lets us lock the hit counter if sub-templates are contained in a template
	var $parse_php			=  FALSE;		// Whether to parse PHP or not
	var $protect_javascript =  TRUE;		// Protect javascript in conditionals

	var $group_name			= '';			// Group of template being parsed
	var $template_name		= '';			// Name of template being parsed
	var $template_id		= 0;

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

	var $realm				= 'Restricted Content';  // Localize?

	var $marker = '0o93H7pQ09L8X1t49cHY01Z5j4TT91fGfr'; // Temporary marker used as a place-holder for template data

	var $form_id			= '';		// 	Form Id
	var $form_class 		= '';		// 	Form Class

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		if (ee()->config->item('multiple_sites_enabled') != 'y')
		{
			$this->sites[ee()->config->item('site_id')] = ee()->config->item('site_short_name');
		}

		if (ee()->config->item('template_debugging') === 'y' && ee()->session->userdata['group_id'] == 1)
		{
			$this->debugging = TRUE;

			$this->start_microtime = microtime(TRUE);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Run Template Engine
	 *
	 * Upon a Page or a Preview, it Runs the Processing of a Template
	 * based on URI request or method arguments
	 *
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function run_template_engine($template_group = '', $template = '')
	{
		$this->log_item(" - Begin Template Processing - ");

		// Set the name of the cache folder for both tag and page caching

		if (ee()->uri->uri_string != '')
		{
			$this->t_cache_path .= md5(ee()->functions->fetch_site_index().ee()->uri->uri_string).'/';
			$this->p_cache_path .= md5(ee()->functions->fetch_site_index().ee()->uri->uri_string).'/';
		}
		else
		{
			$this->t_cache_path .= md5(ee()->config->item('site_url').'index'.ee()->uri->query_string).'/';
			$this->p_cache_path .= md5(ee()->config->item('site_url').'index'.ee()->uri->query_string).'/';
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

				$max = ( ! ee()->config->item('max_caches') OR ! is_numeric(ee()->config->item('max_caches')) OR ee()->config->item('max_caches') > 1000) ? 1000 : ee()->config->item('max_caches');

				if ($i > $max)
				{
					ee()->functions->clear_caching('page');
				}
			}
		}

		$this->log_item("URI: ".ee()->uri->uri_string);
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

		ee()->output->out_type = $this->template_type;
		ee()->output->set_output($this->final_template);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch and Process Template
	 *
	 * Determines what template to process, fetches the template and its preferences, and then processes all of it
	 *
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	int
	 * @return	void
	 */
	public function fetch_and_parse($template_group = '', $template = '', $sub = FALSE, $site_id = '')
	{
		// add this template to our subtemplate tracker
		$this->templates_sofar = $this->templates_sofar.'|'.$site_id.':'.$template_group.'/'.$template.'|';

		// Fetch the requested template
		// The template can either come from the DB or a cache file
		// Do not use a reference!

		$this->cache_status = 'NO_CACHE';

		$this->log_item("Retrieving Template");

		$this->template = ($template_group != '' AND $template != '') ?
			$this->fetch_template($template_group, $template, FALSE, $site_id) :
			$this->parse_template_uri();

		$this->log_item("Template Type: ".$this->template_type);

		$this->parse($this->template, $sub, $site_id);

		// -------------------------------------------
		// 'template_post_parse' hook.
		//  - Modify template after tag parsing
		//
		if (ee()->extensions->active_hook('template_post_parse') === TRUE)
		{
			$this->final_template = ee()->extensions->call(
				'template_post_parse',
				$this->final_template,
				$sub,
				$site_id
			);
		}
		//
		// -------------------------------------------
	}

	// --------------------------------------------------------------------

	/**
	 * Parse a string as a template
	 *
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function parse(&$str, $sub = FALSE, $site_id = '')
	{
		if ($str != '')
		{
			$this->template =& $str;
		}

		// Static Content, No Parsing
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
		if (ee()->config->item('smart_static_parsing') !== 'n' && $this->embed_type == 'webpage' && ! stristr($this->template, LD) && ! stristr($this->template, '<?'))
		{
			$this->log_item("Smart Static Parsing Triggered");

			if ($sub == FALSE)
			{
				$this->final_template = $this->template;
			}

			return;
		}

		// Parse 'Site' variables
		$this->log_item("Parsing Site Variables");

		// load site variables into the global_vars array
		foreach (array('site_id', 'site_label', 'site_short_name') as $site_var)
		{
			ee()->config->_global_vars[$site_var] = stripslashes(ee()->config->item($site_var));
		}

		$seg_array = ee()->uri->segment_array();

		// Define some path related global variables
		$added_globals = array(
			'last_segment' => end($seg_array),
			'current_url' => ee()->functions->fetch_current_uri(),
			'current_path' => (ee()->uri->uri_string) ? ee()->uri->uri_string : '/',
			'current_query_string' => http_build_query($_GET) // GET has been sanitized!
		);

		ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $added_globals);

		// Parse manual variables and Snippets
		// These are variables that can be set in the path.php file

		if (count(ee()->config->_global_vars) > 0)
		{
			$this->log_item("Snippets (Keys): ".implode('|', array_keys(ee()->config->_global_vars)));
			$this->log_item("Snippets (Values): ".trim(implode('|', ee()->config->_global_vars)));

			foreach (ee()->config->_global_vars as $key => $val)
			{
				$this->template = str_replace(LD.$key.RD, $val, $this->template);
			}

			// in case any of these variables have EE comments of their own
			$this->template = $this->remove_ee_comments($this->template);
		}

		// Parse URI segments
		// This code lets admins fetch URI segments which become
		// available as:  {segment_1} {segment_2}

		for ($i = 1; $i < 10; $i++)
		{
			$this->template = str_replace(LD.'segment_'.$i.RD, ee()->uri->segment($i), $this->template);
			$this->segment_vars['segment_'.$i] = ee()->uri->segment($i);
		}

		// Parse {embed} tag variables
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
			$this->template = preg_replace('/'.LD.'embed:([^!]+?)'.RD.'/', '', $this->template);
		}

		// Parse date format string "constants"
		foreach (ee()->localize->format as $key => $val)
		{
			$this->template = str_replace(LD.$key.RD, $val, $this->template);
		}

		$this->log_item("Parse Date Format String Constants");

		// Template's Last Edit time {template_edit_date format="%Y %m %d %H:%i:%s"}
		if (strpos($this->template, LD.'template_edit_date') !== FALSE && preg_match_all("/".LD."template_edit_date\s+format=([\"\'])([^\\1]*?)\\1".RD."/", $this->template, $matches))
		{
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$this->template = str_replace($matches[0][$j], ee()->localize->format_date($matches[2][$j], $this->template_edit_date), $this->template);
			}
		}

		// Current time {current_time format="%Y %m %d %H:%i:%s"}
		if (strpos($this->template, LD.'current_time') !== FALSE && preg_match_all("/".LD."current_time\s+format=([\"\'])([^\\1]*?)\\1".RD."/", $this->template, $matches))
		{
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$this->template = str_replace($matches[0][$j], ee()->localize->format_date($matches[2][$j]), $this->template);
			}
		}

		$this->template = str_replace(LD.'current_time'.RD, ee()->localize->now, $this->template);

		$this->log_item("Parse Current Time Variables");

		// Is the main template cached?
		// If a cache file exists for the primary template
		// there is no reason to go further.
		// However we do need to fetch any subtemplates

		if ($this->cache_status == 'CURRENT' AND $sub == FALSE)
		{
			$this->log_item("Cached Template Used");

			$this->template = $this->parse_nocache($this->template);

			// Smite Our Enemies:  Advanced Conditionals
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

		// Parse Input Stage PHP
		if ($this->parse_php == TRUE && $this->php_parse_location == 'input' && $this->cache_status != 'CURRENT')
		{
			$this->log_item("Parsing PHP on Input");
			$this->template = $this->parse_template_php($this->template);
		}

		// Smite Our Enemies:  Conditionals
		$this->log_item("Parsing Segment, Embed, and Global Vars Conditionals");

		$this->template = $this->parse_simple_segment_conditionals($this->template);
		$this->template = $this->simple_conditionals($this->template, $this->embed_vars);
		$this->template = $this->simple_conditionals($this->template, ee()->config->_global_vars);

		// Assign Variables
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

		// Parse Plugin and Module Tags
		$this->tags();

		if ($this->cease_processing === TRUE)
		{
			return;
		}

		// Parse Output Stage PHP
		if ($this->parse_php == TRUE AND $this->php_parse_location == 'output' AND $this->cache_status != 'CURRENT')
		{
			$this->log_item("Parsing PHP on Output");
			$this->template = $this->parse_template_php($this->template);
		}

		// Write the cache file if needed
		if ($this->cache_status == 'EXPIRED')
		{
			$this->template = ee()->functions->insert_action_ids($this->template);
			$this->write_cache_file($this->cache_hash, $this->template, 'template');
		}

		// Parse Our Uncacheable Forms
		$this->template = $this->parse_nocache($this->template);

		// Smite Our Enemies:  Advanced Conditionals
		if (strpos($this->template, LD.'if') !== FALSE)
		{
			$this->log_item("Processing Advanced Conditionals");
			$this->template = $this->advanced_conditionals($this->template);
		}

		// Build finalized template

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
	 * @param	string
	 * @return	void
	 */
	public function process_sub_templates($template)
	{
		// Match all {embed=bla/bla} tags
		$matches = array();

		if ( ! preg_match_all("/(".LD."embed\s*=)(.*?)".RD."/s", $template, $matches))
		{
			return;
		}

		// Loop until we have parsed all sub-templates

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
				$matches[0][$key] = ee()->functions->full_tag($matches[0][$key], $temp);
				$matches[2][$key] = substr(str_replace($matches[1][$key], '', $matches[0][$key]), 0, -1);
				$temp = str_replace($matches[0][$key], '', $temp);
			}
		}

		foreach($matches[2] as $key => $val)
		{
			$parts = preg_split("/\s+/", $val, 2);

			$this->embed_vars = (isset($parts[1])) ? ee()->functions->assign_parameters($parts[1]) : array();

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

			// Determine Site
			$site_id = ee()->config->item('site_id');

			if (stristr($ex[0], ':'))
			{
				$name = substr($ex[0], 0, strpos($ex[0], ':'));

				if (ee()->config->item('multiple_sites_enabled') == 'y' && ! IS_CORE)
				{
					if (count($this->sites) == 0)
					{
						// This should really be cached somewhere
						ee()->db->select('site_id, site_name');
						$sites_query = ee()->db->get('sites');

						foreach($sites_query->result_array() as $row)
						{
							$this->sites[$row['site_id']] = $row['site_name'];
						}
					}

					$site_id = array_search($name, $this->sites);

					if (empty($site_id))
					{
						$site_id = ee()->config->item('site_id');
					}
				}

				$ex[0] = str_replace($name.':', '', $ex[0]);
			}

			// Loop Prevention

			/* -------------------------------------------
			/*	Hidden Configuration Variable
			/*	- template_loop_prevention => 'n'
				Whether or not loop prevention is enabled - y/n
			/* -------------------------------------------*/

			$this->attempted_fetch[] = $ex['0'].'/'.$ex['1'];

			// Tell user if a template has been recursively loaded
			if (substr_count($this->templates_sofar, '|'.$site_id.':'.$ex['0'].'/'.$ex['1'].'|') > 1 &&
				ee()->config->item('template_loop_prevention') != 'n')
			{
				// Set 503 status code, mainly so caching proxies do not cache this
				ee()->output->set_status_header(503, 'Service Temporarily Unavailable');

				// Tell user which template was loaded recursively and in what order
				// so they can easily fix the error
				if (ee()->config->item('debug') >= 1)
				{
					ee()->load->helper(array('html_helper', 'language_helper'));

					$message = '<p>'.sprintf(lang('template_loop'), $ex['0'].'/'.$ex['1']).'</p>'
						.'<p>'.lang('template_load_order').':</p>'
						.ol($this->attempted_fetch);

					ee()->output->show_message(array(
						'heading' => 'Error',
						'content' => $message
					), FALSE);
				}
				// Show nothing if debug is off
				else
				{
					exit;
				}
			}

			// Process Subtemplate
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
	 * @return	void
	 */
	public function parse_tags()
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
				// Process the tag data
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
					$matches[0] = ee()->functions->full_tag($matches[0]);
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

				// if (count($class) == 1)
				// {
				// 	$class[1] = $class[0];
				// }

				foreach($class as $key => $value)
				{
					$class[$key] = trim($value);
				}

				// -----------------------------------------

				// Assign parameters based on the arguments from the tag
				$args  = ee()->functions->assign_parameters($args);

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
							$match[0] = ee()->functions->full_tag($match[0], $block, LD.'if', LD.'\/'."if".RD);
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

				// If part of the tag name is 'random', we treat it as a unique tag and only
				// replace the first occurence so they are all processed individually. This
				// means that tags with parameters such as orderby="random" behave as expected
				// even if they are identical to other tags on the page.

				if (stripos($raw_tag, 'random') !== FALSE)
				{
					$chunk_offset = strpos($this->template, $chunk);

					if ($chunk_offset !== FALSE)
					{
						$this->template = substr_replace($this->template, 'M'.$this->loop_count.$this->marker, $chunk_offset, strlen($chunk));
					}
				}
				else
				{
					$this->template = str_replace($chunk, 'M'.$this->loop_count.$this->marker, $this->template);
				}

				$cfile = md5($chunk); // This becomes the name of the cache file

				// Build a multi-dimensional array containing all of the tag data we've assembled

				$this->tag_data[$this->loop_count]['tag']				= $raw_tag;
				$this->tag_data[$this->loop_count]['class']				= $class[0];
				$this->tag_data[$this->loop_count]['method']			= (isset($class[1])) ? $class[1] : FALSE;
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
	 * @return	void
	 */
	public function tags()
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
	 * @return	void
	 */
	public function process_tags()
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

						if (ee()->config->item('debug') >= 1)
						{
							if (isset($this->tag_data[$i]['tagparts'][1]) &&
								$this->tag_data[$i]['tagparts'][0] == $this->tag_data[$i]['tagparts'][1] &&
								! isset($this->tag_data[$i]['tagparts'][2]))
							{
								unset($this->tag_data[$i]['tagparts'][1]);
							}

							$error  = ee()->lang->line('error_tag_syntax');
							$error .= '<br /><br />';
							$error .= htmlspecialchars(LD);
							$error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);
							$error .= htmlspecialchars(RD);
							$error .= '<br /><br />';
							$error .= ee()->lang->line('error_fix_syntax');

							ee()->output->fatal_error($error);
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
				if (in_array($plugin ,ee()->core->native_plugins))
				{
					require_once PATH_PI."pi.{$plugin}.php";
				}
				else
				{
					require_once PATH_THIRD."{$plugin}/pi.{$plugin}.php";
				}
			}
		}

		foreach ($modules as $module)
		{
			// make sure it's not already included just in case
			if ( ! class_exists($module))
			{
				if (in_array($module, ee()->core->native_modules))
				{
					require_once PATH_MOD."{$module}/mod.{$module}.php";
				}
				else
				{
					require_once PATH_THIRD."{$module}/mod.{$module}.php";
				}
			}
		}

		$this->log_item("Files for Plugins and Modules All Included");

		// Only Retrieve Data if Not Done Before and Modules Being Called
		if (count($this->module_data) == 0 && count(array_intersect($this->modules, $modules)) > 0)
		{
			ee()->db->select('module_version, module_name');
			$query = ee()->db->get('modules');

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
								unset(ee()->TMPL);
								ee()->TMPL = new EE_Template();
								ee()->TMPL->start_microtime = $this->start_microtime;
								ee()->TMPL->template = $TMPL2->tag_data[$i]['params'][$name];
								ee()->TMPL->tag_data	= array();
								ee()->TMPL->var_single = array();
								ee()->TMPL->var_cond	= array();
								ee()->TMPL->var_pair	= array();
								ee()->TMPL->plugins = $TMPL2->plugins;
								ee()->TMPL->modules = $TMPL2->modules;
								ee()->TMPL->parse_tags();
								ee()->TMPL->process_tags();
								ee()->TMPL->loop_count = 0;
								$TMPL2->tag_data[$i]['params'][$name] = ee()->TMPL->template;
								$TMPL2->log = array_merge($TMPL2->log, ee()->TMPL->log);
							}

							foreach (get_object_vars($TMPL2) as $key => $value)
							{
								$this->$key = $value;
							}

							unset($TMPL2);

							ee()->TMPL = $this;
						}
					}
				}

				// Nested Plugins...
				if (in_array($this->tag_data[$i]['class'] , $this->plugins) && strpos($this->tag_data[$i]['block'], LD.'exp:') !== FALSE)
				{
					if ( ! isset($this->tag_data[$i]['params']['parse']) OR $this->tag_data[$i]['params']['parse'] != 'inward')
					{
						$this->log_item("Nested Plugins in Tag, Parsing Outward First");

						$TMPL2 = clone $this;

						while (is_int(strpos($TMPL2->tag_data[$i]['block'], LD.'exp:')))
						{
							unset(ee()->TMPL);
							ee()->TMPL = new EE_Template();
							ee()->TMPL->start_microtime = $this->start_microtime;
							ee()->TMPL->template = $TMPL2->tag_data[$i]['block'];
							ee()->TMPL->tag_data	= array();
							ee()->TMPL->var_single = array();
							ee()->TMPL->var_cond	= array();
							ee()->TMPL->var_pair	= array();
							ee()->TMPL->plugins = $TMPL2->plugins;
							ee()->TMPL->modules = $TMPL2->modules;
							ee()->TMPL->parse_tags();
							ee()->TMPL->process_tags();
							ee()->TMPL->loop_count = 0;
							$TMPL2->tag_data[$i]['block'] = ee()->TMPL->template;
							$TMPL2->log = array_merge($TMPL2->log, ee()->TMPL->log);
						}

						foreach (get_object_vars($TMPL2) as $key => $value)
						{
							$this->$key = $value;
						}

						unset($TMPL2);

						ee()->TMPL = $this;
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

				// Assign Sites for Tag
				$this->_fetch_site_ids();

				// Fetch Form Class/Id Attributes
				$this->tag_data[$i] = $this->_assign_form_params($this->tag_data[$i]);

				// LEGACY CODE
				// Fetch the variables for this particular tag
				// Hopefully, with Jones' new parsing code we should be able to stop using the
				// assign_variables and assign_conditional_variables() methods entirely. -Paul

				$vars = ee()->functions->assign_variables($this->tag_data[$i]['block']);

				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];

				// Assign the class name and method name
				$class_name = ucfirst($this->tag_data[$i]['class']);
				$meth_name = $this->tag_data[$i]['method'];

				// If it's a third party class or a first party module,
				// add the root folder to the loader paths so we can use
				// libraries, models, and helpers

				$package_path = '';

				if ( ! in_array($this->tag_data[$i]['class'], ee()->core->native_plugins))
				{
					$package_path = in_array($this->tag_data[$i]['class'], ee()->core->native_modules) ? PATH_MOD : PATH_THIRD;
					$package_path .= strtolower($this->tag_data[$i]['class'].'/');

					ee()->load->add_package_path($package_path, FALSE);
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

				// This gives proper PHP5 __construct() support in
				// plugins and modules with only a single __construct()
				// and allows them to be named __construct() instead of a
				// PHP4-style contructor.
				if ($meth_name === FALSE && isset($EE))
				{
					if (method_exists($EE, $class_name))
					{
						$meth_name = $class_name;
					}
					elseif (method_exists($EE, '__construct'))
					{
						$meth_name = '__construct';
					}
				}

				// Does method exist?  Is This A Module and Is It Installed?
				if ((in_array($this->tag_data[$i]['class'], $this->modules) &&
							  ! isset($this->module_data[$class_name])) OR
							  ! is_callable(array($EE, $meth_name)))
				{

					$this->log_item("Tag Not Processed: Method Inexistent or Module Not Installed");

					if (ee()->config->item('debug') >= 1)
					{
						if (isset($this->tag_data[$i]['tagparts'][1]) && $this->tag_data[$i]['tagparts'][0] == $this->tag_data[$i]['tagparts'][1] &&
							! isset($this->tag_data[$i]['tagparts'][2]))
						{
							unset($this->tag_data[$i]['tagparts'][1]);
						}

						$error  = ee()->lang->line('error_tag_module_processing');
						$error .= '<br /><br />';
						$error .= htmlspecialchars(LD);
						$error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);
						$error .= htmlspecialchars(RD);
						$error .= '<br /><br />';
						$error .= str_replace('%x', $this->tag_data[$i]['class'], str_replace('%y', $meth_name, ee()->lang->line('error_fix_module_processing')));

						ee()->output->fatal_error($error);
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

				if ((strtolower($class_name) == strtolower($meth_name)) OR ($meth_name == '__construct'))
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
					ee()->load->remove_package_path($package_path);
				}

				// 404 Page Triggered, Cease All Processing of Tags From Now On
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
	 * ee()->TMPL->related_data array contains anything.  If so, we'll celebrate
	 * wildly.
	 *
	 * @param	string
	 * @return	string
	 */
	public function assign_relationship_data($chunk)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6');

		$this->related_markers = array();

			if (preg_match_all("/".LD."related_entries\s+id\s*=\s*[\"\'](.+?)[\"\']".RD."(.+?)".LD.'\/'."related_entries".RD."/is", $chunk, $matches))
		{
			$this->log_item("Assigning Related Entry Data");

			$no_rel_content = '';

			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$rand = ee()->functions->random('alnum', 8);
				$marker = LD.'REL['.$matches[1][$j].']'.$rand.'REL'.RD;

				if (preg_match("/".LD."if no_related_entries".RD."(.*?)".LD.'\/'."if".RD."/s", $matches[2][$j], $no_rel_match))
				{
					// Match the entirety of the conditional

					if (stristr($no_rel_match[1], LD.'if'))
					{
						$match[0] = ee()->functions->full_tag($no_rel_match[0], $matches[2][$j], LD.'if', LD.'\/'."if".RD);
					}

					$no_rel_content = substr($no_rel_match[0], strlen(LD."if no_related_entries".RD), -strlen(LD.'/'."if".RD));
				}

				$this->related_markers[] = $matches[1][$j];
				$vars = ee()->functions->assign_variables($matches[2][$j]);
				$this->related_id = $matches[1][$j];
				$this->related_data[$rand] = array(
											'marker'			=> $rand,
											'field_name'		=> $matches[1][$j],
											'tagdata'			=> $matches[2][$j],
											'var_single'		=> $vars['var_single'],
											'var_pair' 			=> $vars['var_pair'],
											'var_cond'			=> ee()->functions->assign_conditional_variables($matches[2][$j], '\/', LD, RD),
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
				$rand = ee()->functions->random('alnum', 8);
				$marker = LD.'REV_REL['.$rand.']REV_REL'.RD;
				$vars = ee()->functions->assign_variables($matches[2][$j]);

				$no_rev_content = '';

				if (preg_match("/".LD."if no_reverse_related_entries".RD."(.*?)".LD.'\/'."if".RD."/s", $matches[2][$j], $no_rev_match))
				{
					// Match the entirety of the conditional

					if (stristr($no_rev_match[1], LD.'if'))
					{
						$match[0] = ee()->functions->full_tag($no_rev_match[0], $matches[2][$j], LD.'if', LD.'\/'."if".RD);
					}

					$no_rev_content = substr($no_rev_match[0], strlen(LD."if no_reverse_related_entries".RD), -strlen(LD.'/'."if".RD));
				}

				$this->reverse_related_data[$rand] = array(
															'marker'			=> $rand,
															'tagdata'			=> $matches[2][$j],
															'var_single'		=> $vars['var_single'],
															'var_pair' 			=> $vars['var_pair'],
															'var_cond'			=> ee()->functions->assign_conditional_variables($matches[2][$j], '\/', LD, RD),
															'params'			=> ee()->functions->assign_parameters($matches[1][$j]),
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
	 * @access	string
	 * @access	bool
	 * @return	string
	 */
	public function fetch_param($which, $default = FALSE)
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
	 * @deprecated
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function swap_var_single($search, $replace, $source)
	{
		return str_replace(LD.$search.RD, $replace, $source);
	}

	// --------------------------------------------------------------------

	/**
	 * Seems to Take a Variable Pair and Replace it With Its COntents
	 *
	 * LEGACY!!!
	 *
	 * @deprecated
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function swap_var_pairs($open, $close, $source)
	{
		return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", "\\1", $source);
	}

	// --------------------------------------------------------------------

	/**
	 * Completely Removes a Variable Pair
	 *
	 * LEGACY!!!
	 *
	 * @deprecated
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function delete_var_pairs($open, $close, $source)
	{
		return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", "", $source);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetches Variable Pair's Content
	 *
	 * LEGACY!!!
	 *
	 * @deprecated
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function fetch_data_between_var_pairs($str, $variable)
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
	 * @param	string
	 * @return	string
	 */
	public function parse_template_php($str)
	{
		ob_start();

		echo ee()->functions->evaluate($str);

		$str = ob_get_contents();

		ob_end_clean();

		$this->parse_php = FALSE;

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Cache File Data
	 *
	 * @param	string
	 * @param	string
	 * @param	mixed
	 * @return	string
	 */
	public function fetch_cache_file($cfile, $cache_type = 'tag', $args = array())
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
			$this->cache_timestamp = (int) $timestamp;
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
	 * @param	string
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function write_cache_file($cfile, $data, $cache_type = 'tag')
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

		if ($cache_type == 'tag' && ee()->config->item('disable_tag_caching') == 'y')
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
	 * @return	string
	 */
	public function parse_template_uri()
	{
		$this->log_item("Parsing Template URI");

		// Does the first segment exist?  No?  Show the default template
		if (ee()->uri->segment(1) === FALSE)
		{
			return $this->fetch_template('', 'index', TRUE);
        }
        // Is only the pagination showing in the URI?
        elseif(count(ee()->uri->segments) == 1 &&
        		preg_match("#^(P\d+)$#", ee()->uri->segment(1), $match))
        {
        	ee()->uri->query_string = $match['1'];
        	return $this->fetch_template('', 'index', TRUE);
        }

        // Set the strict urls pref
        if (ee()->config->item('strict_urls') !== FALSE)
        {
        	$this->strict_urls = (ee()->config->item('strict_urls') == 'y') ? TRUE : FALSE;
        }

		// At this point we know that we have at least one segment in the URI, so
		// let's try to determine what template group/template we should show

		// Is the first segment the name of a template group?
		ee()->db->select('group_id');
		ee()->db->where('group_name', ee()->uri->segment(1));
		ee()->db->where('site_id', ee()->config->item('site_id'));
		$query = ee()->db->get('template_groups');

		// Template group found!
		if ($query->num_rows() == 1)
		{
			// Set the name of our template group
			$template_group = ee()->uri->segment(1);

			$this->log_item("Template Group Found: ".$template_group);

			// Set the group_id so we can use it in the next query
			$group_id = $query->row('group_id');

			// Does the second segment of the URI exist? If so...
			if (ee()->uri->segment(2) !== FALSE)
			{
				// Is the second segment the name of a valid template?
				ee()->db->select('COUNT(*) as count');
				ee()->db->where('group_id', $group_id);
				ee()->db->where('template_name', ee()->uri->segment(2));
				$query = ee()->db->get('templates');

				// We have a template name!
				if ($query->row('count') == 1)
				{
					// Assign the template name
					$template = ee()->uri->segment(2);

					// Re-assign the query string variable in the Input class so the various tags can show the correct data
					ee()->uri->query_string = ( ! ee()->uri->segment(3) AND ee()->uri->segment(2) != 'index') ? '' : trim_slashes(substr(ee()->uri->uri_string, strlen('/'.ee()->uri->segment(1).'/'.ee()->uri->segment(2))));
				}
				else // A valid template was not found
				{
					// is there a file we can automatically create this template from?
					if (ee()->config->item('save_tmpl_files') == 'y' && ee()->config->item('tmpl_file_basepath') != '')
					{
						if ($this->_create_from_file($template_group, ee()->uri->segment(2)))
						{
							return $this->fetch_template($template_group, ee()->uri->segment(2), FALSE);
						}
					}

					// Set the template to index
					$template = 'index';

					// Re-assign the query string variable in the Input class so the various tags can show the correct data
					ee()->uri->query_string = ( ! ee()->uri->segment(3)) ? ee()->uri->segment(2) : trim_slashes(substr(ee()->uri->uri_string, strlen('/'.ee()->uri->segment(1))));
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
			if ($query->num_rows() > 1)
			{
				$duplicate = TRUE;
				$log_message = "Duplicate Template Group: ".ee()->uri->segment(1);
			}
			else
			{
				$duplicate = FALSE;
				$log_message = "Template group and template not found, showing 404 page";
			}

			// If we are enforcing strict URLs we need to show a 404
			if ($duplicate == TRUE OR $this->strict_urls == TRUE)
			{
				// is there a file we can automatically create this template from?
				if ($duplicate == FALSE && ee()->config->item('save_tmpl_files') == 'y' && ee()->config->item('tmpl_file_basepath') != '')
				{
					if ($this->_create_from_file(ee()->uri->segment(1), ee()->uri->segment(2)))
					{
						return $this->fetch_template(ee()->uri->segment(1), ee()->uri->segment(2), FALSE);
					}
				}

				if (ee()->config->item('site_404'))
				{
					$this->log_item($log_message);
					return $this->fetch_template('', '', FALSE);
				}
				else
				{
					$this->log_item($log_message);
					return $this->_404();
				}
			}

			// We we are not enforcing strict URLs, so Let's fetch the the name of the default template group
			$result = ee()->db->select('group_name, group_id')
				->get_where(
					'template_groups',
					array(
						'is_site_default' => 'y',
						'site_id' => ee()->config->item('site_id')
					)
				);

			// No result?  Bail out...
			// There's really nothing else to do here.  We don't have a valid
			// template group in the URL and the admin doesn't have a template
			// group defined as the site default.
			if ($result->num_rows() == 0)
			{
				// Turn off caching
				$this->disable_caching = TRUE;

				// Show the user-specified 404
				if (ee()->config->item('site_404'))
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
			ee()->db->select('COUNT(*) as count');
			ee()->db->where('group_id', $result->row('group_id'));
			ee()->db->where('template_name', ee()->uri->segment(1));
			$query = ee()->db->get('templates');

			// We found a valid template!
			if ($query->row('count') == 1)
			{
				// Set the template group name from the prior query result (we
				// use the default template group name)
				$template_group	= $result->row('group_name');

				$this->log_item("Template Group Using Default: ".$template_group);

				// Set the template name
				$template = ee()->uri->segment(1);

				// Re-assign the query string variable in the Input class so the
				// various tags can show the correct data
				if (ee()->uri->segment(2))
				{
					ee()->uri->query_string = trim_slashes(substr(
						ee()->uri->uri_string,
						strlen('/'.ee()->uri->segment(1))
					));
				}
			}
			// A valid template was not found. At this point we do not have
			// either a valid template group or a valid template name in the URL
			else
			{
				// is there a file we can automatically create this template from?
				if (ee()->config->item('save_tmpl_files') == 'y'
					&& ee()->config->item('tmpl_file_basepath') != '')
				{
					if ($this->_create_from_file(ee()->uri->segment(1), ee()->uri->segment(2)))
					{
						return $this->fetch_template(
							ee()->uri->segment(1),
							ee()->uri->segment(2),
							FALSE
						);
					}
				}

				// Turn off caching
				$this->disable_caching = TRUE;

				// Default to site's index template
				ee()->uri->query_string = trim_slashes(
					ee()->uri->uri_string
				);
				$template_group	= $result->row('group_name');
				$template = 'index';
			}
		}

		// Fetch the template!
       return $this->fetch_template($template_group, $template, FALSE);
    }

	// --------------------------------------------------------------------

	/**
	 * 404 Page
	 *
	 * If users do not have a 404 template specified this is what gets shown
	 *
	 * @return	string
	 */
	protected function _404()
	{
		$this->log_item("404 Page Returned");
		ee()->output->set_status_header(404);
		echo '<html><head><title>404 Page Not Found</title></head><body><h1>Status: 404 Page Not Found</h1></body></html>';
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Template Data
	 *
	 * Takes a Template Group, Template, and Site ID and will retrieve the Template and its metadata
	 * from the database (or file)
	 *
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	int
	 * @return	string
	 */
	public function fetch_template($template_group, $template, $show_default = TRUE, $site_id = '')
	{
		if ($site_id == '' OR ! is_numeric($site_id))
		{
			$site_id = ee()->config->item('site_id');
		}

		$this->log_item("Retrieving Template from Database: ".$template_group.'/'.$template);

		$show_404 = FALSE;
		$template_group_404 = '';
		$template_404 = '';

		/* -------------------------------------------
		/*	Hidden Configuration Variable
		/*	- hidden_template_indicator => '.'
			The character(s) used to designate a template as "hidden"
		/* -------------------------------------------*/

		$hidden_indicator = (ee()->config->item('hidden_template_indicator') === FALSE) ? '.' : ee()->config->item('hidden_template_indicator');

		if ($this->depth == 0
			AND substr($template, 0, 1) == $hidden_indicator
			AND ee()->uri->page_query_string == '') // Allow hidden templates to be used for Pages requests
		{
			/* -------------------------------------------
			/*	Hidden Configuration Variable
			/*	- hidden_template_404 => y/n
				If a hidden template is encountered, the default behavior is
				to throw a 404.  With this set to 'n', the template group's
				index page will be shown instead
			/* -------------------------------------------*/

			if (ee()->config->item('hidden_template_404') !== 'n')
			{
				$x = explode("/", ee()->config->item('site_404'));

				if (isset($x[0]) AND isset($x[1]))
				{
					ee()->output->out_type = '404';
					$this->template_type = '404';

					$template_group_404 = ee()->db->escape_str($x[0]);
					$template_404 = ee()->db->escape_str($x[1]);

					ee()->db->where(array(
						'template_groups.group_name'	=> $x[0],
						'templates.template_name'		=> $x[1]
					));

					$show_404 = TRUE;
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

		if ($template_group == '' && $show_default == FALSE && ee()->config->item('site_404') != '')
		{
			$treq = ee()->config->item('site_404');

			$x = explode("/", $treq);

			if (isset($x[0]) AND isset($x[1]))
			{
				ee()->output->out_type = '404';
				$this->template_type = '404';

				$template_group_404 = ee()->db->escape_str($x[0]);
				$template_404 = ee()->db->escape_str($x[1]);

				ee()->db->where(array(
					'template_groups.group_name'	=> $x[0],
					'templates.template_name'		=> $x[1]
				));

				$show_404 = TRUE;
			}
		}

		ee()->db->select('templates.*, template_groups.group_name')
			->from('templates')
			->join('template_groups', 'template_groups.group_id = templates.group_id')
			->where('template_groups.site_id', $site_id);

		// If we're not dealing with a 404, what template and group do we need?
		if ($show_404 === FALSE)
		{
			// Definitely need a template
			if ($template != '')
			{
				ee()->db->where('templates.template_name', $template);
			}

			// But do we have a template group?
			if ($show_default == TRUE)
			{
				ee()->db->where('template_groups.is_site_default', 'y');
			}
			else
			{
				ee()->db->where('template_groups.group_name', $template_group);
			}
		}

		$query = ee()->db->get();

		// Hmm, no template huh?
		if ($query->num_rows() == 0)
		{
			// is there a file we can automatically create this template from?
			if (ee()->config->item('save_tmpl_files') == 'y' && ee()->config->item('tmpl_file_basepath') != '')
			{
				$t_group = ($show_404) ? $template_group_404 : $template_group;
				$t_template = ($show_404) ? $template_404 : $template;

				if ($t_new_id = $this->_create_from_file($t_group, $t_template, TRUE))
				{
					// run the query again, as we just successfully created it
					$query = ee()->db->select('templates.*, template_groups.group_name')
						->join('template_groups', 'template_groups.group_id = templates.group_id')
						->where('templates.template_id', $t_new_id)
						->get('templates');
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

		// HTTP Authentication
		if ($query->row('enable_http_auth') == 'y')
		{
			$this->log_item("HTTP Authentication in Progress");

			ee()->db->select('member_group');
			ee()->db->where('template_id', $query->row('template_id'));
			$results = ee()->db->get('template_no_access');

			$not_allowed_groups = array();

			if ($results->num_rows() > 0)
			{
				foreach($results->result_array() as $row)
				{
					$not_allowed_groups[] = $row['member_group'];
				}
			}

			ee()->load->library('auth');
			ee()->auth->authenticate_http_basic(
				$not_allowed_groups,
				$this->realm
			);
		}

		// Is the current user allowed to view this template?
		if ($query->row('enable_http_auth') != 'y' && $query->row('no_auth_bounce')  != '')
		{
			$this->log_item("Determining Template Access Privileges");

			if (ee()->session->userdata('group_id') != 1)
			{
				ee()->db->select('COUNT(*) as count');
				ee()->db->where('template_id', $query->row('template_id'));
				ee()->db->where('member_group', ee()->session->userdata('group_id'));
				$result = ee()->db->get('template_no_access');

				if ($result->row('count') > 0)
				{
					if ($this->depth > 0)
					{
						return '';
					}

					$query = ee()->db->select('a.template_id, a.template_data,
						a.template_name, a.template_type, a.edit_date,
						a.save_template_file, a.cache, a.refresh, a.hits,
						a.allow_php, a.php_parse_location, b.group_name')
						->from('templates a')
						->join('template_groups b', 'a.group_id = b.group_id')
						->where('template_id', $query->row('no_auth_bounce'))
						->get();
				}
			}
		}

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$row = $query->row_array();

		// Is PHP allowed in this template?
		if ($row['allow_php'] == 'y')
		{
			$this->parse_php = TRUE;

			$this->php_parse_location = ($row['php_parse_location'] == 'i') ? 'input' : 'output';
		}

		// Increment hit counter
		if (($this->hit_lock == FALSE OR $this->hit_lock_override == TRUE) &&
			ee()->config->item('enable_hit_tracking') != 'n')
		{
			$this->template_hits = $row['hits'] + 1;
			$this->hit_lock = TRUE;

			ee()->db->update(
				'templates',
				array('hits' 		=> $this->template_hits),
				array('template_id'	=> $row['template_id'])
			);
		}

		// Set template edit date
		$this->template_edit_date = $row['edit_date'];

		// Set template type for our page headers
		if ($this->template_type == '')
		{
			$this->template_type = $row['template_type'];
			ee()->functions->template_type = $row['template_type'];

			// If JS or CSS request, reset Tracker Cookie
			if ($this->template_type == 'js' OR $this->template_type == 'css')
			{
				if (count(ee()->session->tracker) <= 1)
				{
					ee()->session->tracker = array();
				}
				else
				{
					$removed = array_shift(ee()->session->tracker);
				}

				ee()->functions->set_cookie('tracker', serialize(ee()->session->tracker), '0');
			}
		}

		if ($this->depth > 0)
		{
			$this->embed_type = $row['template_type'];
		}

		// Cache Override

		// We can manually set certain things not to be cached, like the
		// search template and the member directory after it's updated

	 	// Note: I think search caching is OK.
		// $cache_override = array('member' => 'U', 'search' => FALSE);

		$cache_override = array('member');

		foreach ($cache_override as $val)
		{
			if (strncmp(ee()->uri->uri_string, "/{$val}/", strlen($val) + 2) == 0)
			{
				$row['cache'] = 'n';
			}
		}

		// Retreive cache
		$this->cache_hash = md5($site_id.'-'.$template_group.'-'.$template);

		if ($row['cache'] == 'y')
		{
			$cache_contents = $this->fetch_cache_file($this->cache_hash, 'template', array('cache' => 'yes', 'refresh' => $row['refresh']));

			if ($this->cache_status == 'CURRENT')
			{
				$row['template_data'] = $cache_contents;

				// -------------------------------------------
				// 'template_fetch_template' hook.
				//  - Access template data prior to template parsing
				//
					if (ee()->extensions->active_hook('template_fetch_template') === TRUE)
					{
						ee()->extensions->call('template_fetch_template', $row);
					}
				//
				// -------------------------------------------

				return $this->convert_xml_declaration($cache_contents);
			}
		}

		// Retrieve template file if necessary
		if ($row['save_template_file'] == 'y')
		{
			$site_switch = FALSE;

			if (ee()->config->item('site_id') != $site_id)
			{
				$site_switch = ee()->config->config;

				if (isset($this->site_prefs_cache[$site_id]))
				{
					ee()->config->config = $this->site_prefs_cache[$site_id];
				}
				else
				{
					ee()->config->site_prefs('', $site_id);
					$this->site_prefs_cache[$site_id] = ee()->config->config;
				}
			}

			if (ee()->config->item('save_tmpl_files') == 'y'
				AND ee()->config->item('tmpl_file_basepath') != '')
			{
				$this->log_item("Retrieving Template from File");
				ee()->load->library('api');
				ee()->api->instantiate('template_structure');

				$basepath = rtrim(ee()->config->item('tmpl_file_basepath'), '/').'/';

				$basepath .= ee()->config->item('site_short_name').'/'
					.$row['group_name'].'.group/'.$row['template_name']
					.ee()->api_template_structure->file_extensions($row['template_type']);

				if (file_exists($basepath))
				{
					$row['template_data'] = file_get_contents($basepath);
				}
			}

			if ($site_switch !== FALSE)
			{
				ee()->config->config = $site_switch;
			}
        }

		// standardize newlines
		$row['template_data'] =  str_replace(array("\r\n", "\r"), "\n", $row['template_data']);

		// -------------------------------------------
		// 'template_fetch_template' hook.
		//  - Access template data prior to template parsing
		//
			if (ee()->extensions->active_hook('template_fetch_template') === TRUE)
			{
				ee()->extensions->call('template_fetch_template', $row);
			}
		//
		// -------------------------------------------

		// remember what template we're on
		$this->group_name = $row['group_name'];
		$this->template_id = $row['template_id'];
		$this->template_name = $row['template_name'];

		return $this->convert_xml_declaration($this->remove_ee_comments($row['template_data']));
	}

	// --------------------------------------------------------------------

	/**
	 * Create From File
	 *
	 * Attempts to create a template group / template from a file
	 *
	 * @param	string		template group name
	 * @param	string		template name
	 * @return	bool
	 */
	function _create_from_file($template_group, $template, $db_check = FALSE)
	{
		if (ee()->config->item('save_tmpl_files') != 'y' OR ee()->config->item('tmpl_file_basepath') == '')
		{
			return FALSE;
		}

		$template = ($template == '') ? 'index' : $template;

		// Template Groups and Templates are limited to 50 characters in db
		if (strlen($template) > 50 OR strlen($template_group) > 50)
		{
			return FALSE;
		}

		if ($db_check)
		{
			ee()->db->from('templates');
			ee()->db->join('template_groups', 'templates.group_id = template_groups.group_id', 'left');
			ee()->db->where('group_name', $template_group);
			ee()->db->where('template_name', $template);
			$valid_count =  ee()->db->count_all_results();

			// We found a valid template!  Er- could this loop?  Better just return FALSE
			if ($valid_count > 0)
			{
				return FALSE;
			}
		}

		ee()->load->library('api');
		ee()->api->instantiate('template_structure');
		ee()->load->model('template_model');

		$basepath = ee()->config->slash_item('tmpl_file_basepath').ee()->config->item('site_short_name').'/'.$template_group.'.group';

		if ( ! is_dir($basepath))
		{
			return FALSE;
		}

		$filename = FALSE;

		// Note- we should add the extension before checking.

		foreach (ee()->api_template_structure->file_extensions as $type => $temp_ext)
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

		if ( ! ee()->api->is_url_safe($template))
		{
			// bail out
			return FALSE;
		}

		ee()->db->select('group_id');
		ee()->db->where('group_name', $template_group);
		ee()->db->where('site_id', ee()->config->item('site_id'));
		$query = ee()->db->get('template_groups');

		if ($query->num_rows() != 0)
		{
			$group_id = $query->row('group_id');
		}
		else
		{
			// we have a new group to create!
			if ( ! ee()->api->is_url_safe($template_group))
			{
				// bail out
				return FALSE;
			}

			if (in_array($template_group, ee()->api_template_structure->reserved_names))
			{
				// bail out
				return FALSE;
			}

			$data = array(
				'group_name'		=> $template_group,
				'group_order'		=> ee()->db->count_all('template_groups') + 1,
				'is_site_default'	=> 'n',
				'site_id'			=> ee()->config->item('site_id')
			);

			$group_id = ee()->template_model->create_group($data);
		}

		$data = array(
			'group_id'				=> $group_id,
			'template_name'			=> $template,
			'template_type'			=> $template_type,
			'template_data'			=> file_get_contents($basepath.'/'.$filename),
			'edit_date'				=> ee()->localize->now,
			'save_template_file'	=> 'y',
			'last_author_id'		=> '1',	// assume a super admin
			'site_id'				=> ee()->config->item('site_id')
		 );

		$template_id = ee()->template_model->create_template($data);

		// Clear db cache or it will create a new template record each page load!
		ee()->functions->clear_caching('db');

		return $template_id;
	}

	// --------------------------------------------------------------------

	/**
	 * No Results
	 *
	 * If a tag/class has no results to show, it can call this method.  Any no_results variable in
	 * the tag will be followed.  May be 404 page, content, or even a redirect.
	 *
	 * @return	void
	 */
	public function no_results()
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
				$template = explode('/', ee()->config->item('site_404'));

				if (isset($template[1]))
				{
					$this->log_item('Processing "'.$template[0].'/'.$template[1].'" Template as 404 Page');
					ee()->output->out_type = "404";
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
				return ee()->functions->redirect(ee()->functions->create_url(ee()->functions->extract_path("=".$match[2])));
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
	 * This fixes a parsing error when PHP is used in RSS templates
	 *
	 * @param	string
	 * @return	string
	 */
	public function convert_xml_declaration($str)
	{
		if (strpos($str, '<?xml') === FALSE) return $str;

		return preg_replace("/\<\?xml(.+?)\?\>/", "<XXML\\1/XXML>", $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Restore XML Declaration
	 *
	 * @param	string
	 * @return	string
	 */
	public function restore_xml_declaration($str)
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
	 * @param	string
	 * @return	string
	 */
	public function remove_ee_comments($str)
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
	 * @return	void
	 */
	public function fetch_addons()
	{
		ee()->load->helper('file');
		ee()->load->helper('directory');
		$ext_len = strlen('.php');

		// first get first party modules
		if (($map = directory_map(PATH_MOD, TRUE)) !== FALSE)
		{
			foreach ($map as $file)
			{
				if (strpos($file, '.') === FALSE)
				{
					if (IS_CORE && in_array($file, ee()->core->standard_modules))
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
				if (strncasecmp($file, 'pi.', 3) == 0 &&
					substr($file, -$ext_len) == '.php' &&
					strlen($file) > strlen('pi..php') &&
					in_array(substr($file, 3, -$ext_len), ee()->core->native_plugins))
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
					if (strncasecmp($file, 'mod.', 4) == 0 &&
						substr($file, -$ext_len) == '.php' &&
						strlen($file) > strlen('mod..php'))
					{
						$file = substr($file, 4, -$ext_len);

						if ($file == $pkg_name)
						{
							$this->modules[] = $file;
						}
					}
					// how abouts a plugin?
					elseif (strncasecmp($file, 'pi.', 3) == 0 &&
							substr($file, -$ext_len) == '.php' &&
							strlen($file) > strlen('pi..php'))
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
	 * @param	string
	 * @return	string
	 */
	public function parse_globals($str)
	{
		$charset 	= '';
		$lang		= '';
		$user_vars	= array(
					'member_id', 'group_id', 'group_description',
					'group_title', 'member_group', 'username', 'screen_name',
					'email', 'ip_address', 'location', 'total_entries',
					'total_comments', 'private_messages', 'total_forum_posts',
					'total_forum_topics', 'total_forum_replies'
				);

		// Redirect - if we have one of these, no need to go further
		if (strpos($str, LD.'redirect') !== FALSE)
		{
			if (preg_match("/".LD."redirect\s*=\s*(\042|\047)([^\\1]*?)\\1\s*(status_code\s*=\s*(\042|\047)([^\\4]*?)\\4)?".RD."/si", $str, $match))
			{
				if ($match['2'] == "404")
				{
					$template = explode('/', ee()->config->item('site_404'));

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
					// If we don't have a status code, we'll send NULL
					// to redirect() which result in no status code being set and
					// header using the default 302.
					// If the status code isn't a 3xx redirect code, it will be ignored
					// by redirect().
					$status_code = NULL;

					if (isset($match[5]))
					{
						$status_code = $match[5];
					}

					// Functions::redirect() exits on its own
					ee()->functions->redirect(
						ee()->functions->create_url(ee()->functions->extract_path("=".$match['2'])),
						FALSE,
						$status_code
					);
				}
			}
		}

		// Restore XML declaration if it was encoded
		$str = $this->restore_xml_declaration($str);

		//  Parse User-defined Global Variables first so that
		//  they can use other standard globals
		ee()->db->select('variable_name, variable_data');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		$query = ee()->db->get('global_variables');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$str = str_replace(LD.$row['variable_name'].RD, $row['variable_data'], $str);
			}
		}

		// {hits}
		$str = str_replace(LD.'hits'.RD, $this->template_hits, $str);

		// {ip_address} and {ip_hostname}
		$str = str_replace(LD.'ip_address'.RD, ee()->input->ip_address(), $str);

		// Turns out gethostbyaddr() is WAY SLOW on many systems so I'm killing it.
		// $str = str_replace(LD.'ip_hostname'.RD, @gethostbyaddr(ee()->input->ip_address()), $str);

		$str = str_replace(LD.'ip_hostname'.RD, ee()->input->ip_address(), $str);

		// {homepage}
		$str = str_replace(LD.'homepage'.RD, ee()->functions->fetch_site_index(), $str);

		//  {cp_url}
		if (ee()->session->access_cp === TRUE)
		{
			$str = str_replace(LD.'cp_url'.RD, ee()->config->item('cp_url'), $str);
		}
		else
		{
			$str = str_replace(LD.'cp_url'.RD, '', $str);
		}

		// {site_name} {site_url} {site_index} {webmaster_email}
		$str = str_replace(LD.'site_name'.RD, stripslashes(ee()->config->item('site_name')), $str);
		$str = str_replace(LD.'site_url'.RD, stripslashes(ee()->config->item('site_url')), $str);
		$str = str_replace(LD.'site_index'.RD, stripslashes(ee()->config->item('site_index')), $str);
		$str = str_replace(LD.'webmaster_email'.RD, stripslashes(ee()->config->item('webmaster_email')), $str);

		// Stylesheet variable: {stylesheet=group/template}
		if (strpos($str, 'stylesheet=') !== FALSE && preg_match_all("/".LD."\s*stylesheet=[\042\047]?(.*?)[\042\047]?".RD."/", $str, $css_matches))
        {
        	$css_versions = array();

        	if (ee()->config->item('send_headers') == 'y')
        	{
        		$sql = "SELECT t.template_name, tg.group_name, t.edit_date, t.save_template_file FROM exp_templates t, exp_template_groups tg
        				WHERE  t.group_id = tg.group_id
        				AND    t.template_type = 'css'
        				AND    t.site_id = '".ee()->db->escape_str(ee()->config->item('site_id'))."'";

        		foreach($css_matches[1] as $css_match)
        		{
        			$ex = explode('/', $css_match, 2);

        			if (isset($ex[1]))
        			{
        				$css_parts[] = "(t.template_name = '".ee()->db->escape_str($ex[1])."' AND tg.group_name = '".ee()->db->escape_str($ex[0])."')";
        			}
        		}

        		$css_query = ( ! isset($css_parts)) ? ee()->db->query($sql) : ee()->db->query($sql.' AND ('.implode(' OR ', $css_parts) .')');

        		if ($css_query->num_rows() > 0)
        		{
        			foreach($css_query->result_array() as $row)
        			{
						$css_versions[$row['group_name'].'/'.$row['template_name']] = $row['edit_date'];

						if (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '' AND $row['save_template_file'] == 'y')
						{
							$basepath = ee()->config->slash_item('tmpl_file_basepath').ee()->config->item('site_short_name').'/';
							$basepath .= $row['group_name'].'.group/'.$row['template_name'].'.css';

							if (is_file($basepath))
							{
								$css_versions[$row['group_name'].'/'.$row['template_name']] = filemtime($basepath);
							}
						}
        			}
        		}
        	}

			$s_index = ee()->functions->fetch_site_index();

			if ( ! QUERY_MARKER && substr($s_index, -1) != '?')
			{
				$s_index .= '&';
			}

			for($ci=0, $cs=count($css_matches[0]); $ci < $cs; ++$ci)
			{
				$str = str_replace($css_matches[0][$ci], $s_index.QUERY_MARKER.'css='.$css_matches[1][$ci].(isset($css_versions[$css_matches[1][$ci]]) ? '.v.'.$css_versions[$css_matches[1][$ci]] : ''), $str);
			}

        	unset($css_matches);
        	unset($css_versions);
        }

		// Email encode: {encode="you@yoursite.com" title="click Me"}
		if (strpos($str, LD.'encode=') !== FALSE)
		{
			if ($this->encode_email == TRUE)
			{
				if (preg_match_all("/".LD."encode=(.+?)".RD."/i", $str, $matches))
				{
					for ($j = 0; $j < count($matches[0]); $j++)
					{
						$str = preg_replace('/'.preg_quote($matches['0'][$j], '/').'/', ee()->functions->encode_email($matches[1][$j]), $str, 1);
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
									(ee()->config->item('encode_removed_text') !== FALSE) ? ee()->config->item('encode_removed_text') : '',
									$str);
			}
		}

		// Debug mode: {debug_mode}
		$str = str_replace(LD.'debug_mode'.RD, (ee()->config->item('debug') > 0) ? ee()->lang->line('on') : ee()->lang->line('off'), $str);

		// GZip mode: {gzip_mode}
		$str = str_replace(LD.'gzip_mode'.RD, (ee()->config->item('gzip_output') == 'y') ? ee()->lang->line('enabled') : ee()->lang->line('disabled'), $str);

		// App version: {version}
		$str = str_replace(LD.'app_version'.RD, APP_VER, $str);
		$str = str_replace(LD.'version'.RD, APP_VER, $str);

		// App version: {build}
		$str = str_replace(LD.'app_build'.RD, APP_BUILD, $str);
		$str = str_replace(LD.'build'.RD, APP_BUILD, $str);

		// {charset} and {lang}
		$str = str_replace(LD.'charset'.RD, ee()->config->item('output_charset'), $str);
		$str = str_replace(LD.'lang'.RD, ee()->config->item('xml_lang'), $str);

		// {doc_url}
		$str = str_replace(LD.'doc_url'.RD, ee()->config->item('doc_url'), $str);

		// {theme_folder_url}
		$str = str_replace(LD.'theme_folder_url'.RD,
			ee()->config->item('theme_folder_url'), $str);

		// {member_profile_link}
		if (ee()->session->userdata('member_id') != 0)
		{
			$name = (ee()->session->userdata['screen_name'] == '') ? ee()->session->userdata['username'] : ee()->session->userdata['screen_name'];

			$path = "<a href='".ee()->functions->create_url('/member/'.ee()->session->userdata('member_id'))."'>".$name."</a>";

			$str = str_replace(LD.'member_profile_link'.RD, $path, $str);
		}
		else
		{
			$str = str_replace(LD.'member_profile_link'.RD, '', $str);
		}

		// Fetch CAPTCHA
		if (strpos($str, "{captcha}") !== FALSE)
		{
			$str = str_replace("{captcha}", ee()->functions->create_captcha(), $str);
		}

		// Add security hashes to forms
		// We do this here to keep the security hashes from being cached

		$str = ee()->functions->add_form_security_hash($str);

		// Parse non-cachable variables
		ee()->session->userdata['member_group'] = ee()->session->userdata['group_id'];

		foreach ($user_vars as $val)
		{
			$replace = (isset(ee()->session->userdata[$val]) && strval(ee()->session->userdata[$val]) != '') ?
				ee()->session->userdata[$val] : '';

			$str = str_replace(LD.$val.RD, $replace, $str);
			$str = str_replace('{out_'.$val.'}', $replace, $str);
			$str = str_replace('{global->'.$val.'}', $replace, $str);
			$str = str_replace('{logged_in_'.$val.'}', $replace, $str);
		}

		// Path variable: {path=group/template}
		if (strpos($str, 'path=') !== FALSE)
		{
			$str = preg_replace_callback("/".LD."\s*path=(.*?)".RD."/", array(&ee()->functions, 'create_url'), $str);
		}

		// Add Action IDs form forms and links
		$str = ee()->functions->insert_action_ids($str);

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
	 * @param	string
	 * @return	string
	 */
	public function parse_nocache($str)
	{
		if (strpos($str, '{NOCACHE') === FALSE)
		{
			return $str;
		}

		// Generate Comment Form if needed

		// In order for the comment form not to cache the "save info"
		// data we need to generate dynamically if necessary

		if (preg_match_all("#{NOCACHE_(\S+)_FORM=\"(.*?)\"}(.+?){/NOCACHE_FORM}#s", $str, $match))
		{
			for($i=0, $s=count($match[0]); $i < $s; $i++)
			{
				$class = ee()->security->sanitize_filename(strtolower($match[1][$i]));

				if ( ! class_exists($class))
				{
					require PATH_MOD.$class.'/mod.'.$class.'.php';
				}

				$this->tagdata = $match[3][$i];

				$vars = ee()->functions->assign_variables($match[3][$i], '/');
				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];

				$this->tagparams = ee()->functions->assign_parameters($match[2][$i]);

				$this->var_cond = ee()->functions->assign_conditional_variables($match[3][$i], '/', LD, RD);

				// Assign sites for the tag
				$this->_fetch_site_ids();

				// Assign Form ID/Classes
				if (isset($this->tag_data[$i]))
				{
					$this->tag_data[$i] = $this->_assign_form_params($this->tag_data[$i]);
				}

				if ($class == 'comment')
				{
					$str = str_replace($match[0][$i], Comment::form(TRUE, ee()->functions->cached_captcha), $str);
				}

				$str = str_replace('{PREVIEW_TEMPLATE}', $match[2][$i], $str);
			}
		}
		/*
		// Generate Stand-alone Publish form
		if (preg_match_all("#{{NOCACHE_CHANNEL_FORM(.*?)}}(.+?){{/NOCACHE_FORM}}#s", $str, $match))
		{
			for($i=0, $s=count($match[0]); $i < $s; $i++)
			{
				if ( ! class_exists('Channel'))
				{
					require PATH_MOD.'channel/mod.channel.php';
				}

				$this->tagdata = $match[2][$i];

				$vars = ee()->functions->assign_variables($match[2][$i], '/');
				$this->var_single	= $vars['var_single'];
				$this->var_pair		= $vars['var_pair'];

				$this->tagparams = ee()->functions->assign_parameters($match[1][$i]);

				// Assign sites for the tag
				$this->_fetch_site_ids();

				// Assign Form ID/Classes
				if (isset($this->tag_data[$i]))
				{
					$this->tag_data[$i] = $this->_assign_form_params($this->tag_data[$i]);
				}

				$XX = new Channel();
				$str = str_replace($match[0][$i], $XX->entry_form(TRUE, ee()->functions->cached_captcha), $str);
				$str = str_replace('{PREVIEW_TEMPLATE}', (isset($_POST['PRV'])) ? $_POST['PRV'] : $this->fetch_param('preview'), $str);
			}
		}
		*/

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
	 * @param	string
	 * @return	string
	 */
	public function advanced_conditionals($str)
	{
		if (stristr($str, LD.'if') === FALSE)
		{
			return $str;
		}

		/* ---------------------------------
		/*	Hidden Configuration Variables
		/*  - protect_javascript => Prevents advanced conditional parser from processing anything in <script> tags
		/* ---------------------------------*/

		if (ee()->config->item('protect_javascript') == 'n')
		{
			$this->protect_javascript = FALSE;
		}

		$user_vars	= array('member_id', 'group_id', 'group_description', 'group_title', 'username', 'screen_name',
							'email', 'ip_address', 'location', 'total_entries',
							'total_comments', 'private_messages', 'total_forum_posts', 'total_forum_topics', 'total_forum_replies');

		for($i=0,$s=count($user_vars), $data = array(); $i < $s; ++$i)
		{
			$data[$user_vars[$i]] = ee()->session->userdata[$user_vars[$i]];
			$data['logged_in_'.$user_vars[$i]] = ee()->session->userdata[$user_vars[$i]];
		}

		// Define an alternate variable for {group_id} since some tags use
		// it natively, causing it to be unavailable as a global

		$data['member_group'] = $data['logged_in_member_group'] = ee()->session->userdata['group_id'];

		// Logged in and logged out variables
		$data['logged_in'] = (ee()->session->userdata['member_id'] == 0) ? 'FALSE' : 'TRUE';
		$data['logged_out'] = (ee()->session->userdata['member_id'] != 0) ? 'FALSE' : 'TRUE';

		// current time
		$data['current_time'] = ee()->localize->now;

		// Member Group in_group('1') function, Super Secret!  Shhhhh!
		if (preg_match_all("/in_group\(([^\)]+)\)/", $str, $matches))
		{
			$groups = (is_array(ee()->session->userdata['group_id'])) ? ee()->session->userdata['group_id'] : array(ee()->session->userdata['group_id']);

			for($i=0, $s=count($matches[0]); $i < $s; ++$i)
			{
				$check = explode('|', str_replace(array('"', "'"), '', $matches[1][$i]));

				$str = str_replace($matches[0][$i], (count(array_intersect($check, $groups)) > 0) ? 'TRUE' : 'FALSE', $str);
			}
		}

		// Final Prep, Safety On
		$str = ee()->functions->prep_conditionals($str, array_merge($this->segment_vars, $this->embed_vars, ee()->config->_global_vars, $data), 'y');

		// Protect Already Existing Unparsed PHP

		$opener = unique_marker('tmpl_php_open');
		$closer = unique_marker('tmpl_php_close');

		$str = str_replace(array('<?', '?'.'>'),
							array($opener.'?', '?'.$closer),
							$str);

		// Protect <script> tags
		$protected = array();
		$front_protect = unique_marker('tmpl_script_open');
		$back_protect  = unique_marker('tmpl_script_close');

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

		// Convert EE Conditionals to PHP
		$str = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $str);

		if (strpos($str, LD.'if') !== FALSE)
		{
			$str = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s+(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $str);
		}

		$str = $this->parse_template_php($str);

		// Unprotect <script> tags
		if (count($protected) > 0)
		{
			$str = str_replace(array_keys($protected), array_values($protected), $str);
		}

		// Unprotect Already Existing Unparsed PHP
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
	 * @param	string
	 * @return	string
	 */
	public function parse_simple_segment_conditionals($str)
	{
		if ( ! preg_match("/".LD."if\s+segment_.+".RD."/", $str))
		{
			return $str;
		}

		$this->var_cond = ee()->functions->assign_conditional_variables($str);

		foreach ($this->var_cond as $val)
		{
			// Make sure this is for a segment conditional
			// And that this is not an advanced conditional

				if ( ! preg_match('/^segment_\d+$/i', $val['3']) OR
				strpos($val[2], 'if:else') !== FALSE OR
				strpos($val[0], 'if:else') !== FALSE OR
				count(preg_split("/(\!=|==|<=|>=|<>|<|>|%|AND|XOR|OR|&&|\|\|)/", $val[0])) > 2)
			{
				continue;
			}

			$cond = ee()->functions->prep_conditional($val[0]);

			$lcond	= substr($cond, 0, strpos($cond, ' '));
			$rcond	= substr($cond, strpos($cond, ' '));

			if (strpos($rcond, '"') == FALSE && strpos($rcond, "'") === FALSE) continue;

			$n = substr($val[3], 8);
			$temp = (isset(ee()->uri->segments[$n])) ? ee()->uri->segments[$n] : '';

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
	public function simple_conditionals($str, $vars = array())
	{
		if (count($vars) == 0 OR ! stristr($str, LD.'if'))
		{
			return $str;
		}

		$this->var_cond = ee()->functions->assign_conditional_variables($str);

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
				count(preg_split("/(\!=|==|<=|>=|<>|<|>|%|AND|XOR|OR|&&|\|\|)/", $val[0])) > 2)
			{
				continue;
			}

			$cond = ee()->functions->prep_conditional($val[0]);

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

		$time = microtime(TRUE)-$this->start_microtime;

		$memory_usage = '';

		if (function_exists('memory_get_usage'))
		{
			$memory_usage = ' / '.number_format(round(memory_get_usage()/1024/1024, 2),2).'MB';
		}

		$this->log[] = '('.number_format($time, 6). $memory_usage . ') '.$str;
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
			if (count($this->sites) == 0 &&
				ee()->config->item('multiple_sites_enabled') == 'y' && ! IS_CORE)
			{
				$sites_query = ee()->db->query("SELECT site_id, site_name FROM exp_sites ORDER BY site_id");

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
			$this->site_ids[] = ee()->config->item('site_id');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Variables
	 *
	 * Simplifies variable parsing for plugin
	 * and modules developers
	 *
	 * @param	string	- the tagdata / text to be parsed
	 * @param	array	- the rows of variables and their data
	 * @param	boolean	- Option to disable backspace parameter
	 * @return	string
	 */
	public function parse_variables($tagdata, $variables, $enable_backspace = TRUE)
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
				$sparam = ee()->functions->assign_parameters($match[1]);

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

		$backspace = $this->fetch_param('backspace', FALSE);

		if (is_numeric($backspace) AND $enable_backspace)
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
	 * @param	string	- the tagdata / text to be parsed
	 * @param	array	- the variables and their data
	 * @param	bool	- coming from parse_variables() or part of set, forces some caching
	 *
	 * @return	string
	 */
	public function parse_variables_row($tagdata, $variables, $solo = TRUE)
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
				if (empty($value))
				{
					// Weirdness. The most likely cause is an empty tag pair, we won't
					// require developers to take care of this. This hack will blank them out.
					$value = array(array());
				}

				if (isset($value[0]) && is_array($value[0]))
				{
					$tagdata = $this->_parse_var_pair($name, $value, $tagdata, 1);
					continue;
				}
			}

			$tagdata = $this->_parse_var_single($name, $value, $tagdata);
		}

		// Prep conditionals
		$tagdata = ee()->functions->prep_conditionals($tagdata, $this->conditional_vars);

		return $tagdata;
	}

	function create_url_check($matches)
	{

		print_r($matches);

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
				$string = str_replace(LD.$dvar.RD,
									  ee()->localize->format_date($dval, $value),
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


		// If the single variable's value is an array, then
		// $value[0] is the content and $value[1] is an array
		// of parameters for the Typography class OR an indicator of a path variable
		if (is_array($value) && count($value) == 2 && is_array($value[1]))
		{
			$raw_content = $value[0];

			// Make our path switches
			if (isset($value[1]['path_variable']) && $value[1]['path_variable'] === TRUE)
			{
				if (preg_match_all("#".LD."\s*".$name."=(.*?)".RD."#", $string, $matches))
				{
					$done = array();

					foreach ($matches[0] as $full)
					{
						if (in_array($full, $done))
						{
							continue;
						}

						$link = ee()->functions->create_url(ee()->functions->extract_path($full).'/'.$value[0]);

					//$single_quote = str_replace("'", '"', $matches['0']);
					//$double_quote = str_replace("'", '"', $matches['0']);

	//[0] => {id_path="about/test"}
    //[1] => "about/test"

					// Switch to double quotes

						$single = str_replace(array('"', "'"), "'", $full);
						$double = str_replace(array('"', "'"), '"', $full);

					//echo $single.' - '.$double.'<br>';
						$string = str_replace($single, $double, $string);
					//echo $string;
					//echo '<br>-----------------------<br>';

						$string = str_replace($double, $link, $string);

						$done[] = $full;

					}
				}

				return $string;
			}


			$prefs = array();

			foreach (array('text_format', 'html_format', 'auto_links', 'allow_img_url', 'convert_curly') as $pref)
			{
				if (isset($value[1][$pref]))
				{
					$prefs[$pref] = $value[1][$pref];
				}
			}

			// Instantiate Typography only if necessary
			ee()->load->library('typography');
			ee()->typography->initialize(array(
				'convert_curly'	=> (isset($prefs['convert_curly']) && $prefs['convert_curly'] == 'n') ? FALSE : TRUE)
				);

			$value = ee()->typography->parse_type($raw_content, $prefs);


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
		if ( ! $match_count = preg_match_all("|".LD.$name.'.*?'.RD.'(.*?)'.LD.'/'.$name.RD."|s", $string, $matches))
		{
			return $string;
		}

		if (empty($variables[0]))
		{
			return str_replace($matches[0], '', $string);
		}

		if ( ! isset($this->unfound_vars[$depth]))
		{
			// created a separate unfound vars for each matched pair (kind of a crazy array, need to investigate if it's hindering performance at this point)
			$this->unfound_vars[$depth] = array();
		}

		foreach ($matches[1] as $k => $match)
		{
			$str = '';
			$parameters = array();
			$count = 1;

			// Get parameters of variable pair
			if (preg_match_all("|".LD.$name.'(.*?)'.RD."|s", $matches[0][$k], $param_matches))
			{
				$parameters = ee()->functions->assign_parameters($param_matches[1][0]);
			}

			// Limit parameter
			$limit = (isset($parameters['limit'])) ? $parameters['limit'] : NULL;

			foreach ($variables as $set)
			{
				$temp = $match;

				foreach ($set as $key => $value)
				{
					if (isset($this->unfound_vars[$depth][$key]) OR
						strpos($string, LD.$key) === FALSE)
					{
						continue;
					}

					// Pair variables are an array of arrays.
					if (is_array($value))
					{
						if (empty($value))
						{
							// Weirdness. The most likely cause is an empty tag pair, we won't
							// require developers to take care of this. This hack will blank them out.
							$value = array(array());
						}

						if (isset($value[0]) && is_array($value[0]))
						{
							$temp = $this->_parse_var_pair($key, $value, $temp, $depth + 1);
							continue;
						}
					}

					$temp = $this->_parse_var_single($key, $value, $temp);
				}

				// Prep conditionals
				$temp = ee()->functions->prep_conditionals($temp, $set);
				$str .= $temp;

				// Break if we're past the limit
				if ($limit !== NULL AND $limit == $count++)
				{
					break;
				}
			}

			// Backspace parameter
			$backspace = (isset($parameters['backspace'])) ? $parameters['backspace'] : NULL;

			if (is_numeric($backspace))
			{
				$str = substr($str, 0, -$backspace);
			}

			$string = str_replace($matches[0][$k], $str, $string);
		}

		return $string;
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

		if (preg_match_all("/".LD."([\w:\-]+)\s+format=[\"'](.*?)[\"']".RD."/", $str, $matches, PREG_SET_ORDER))
		{
			for ($j = 0, $tot = count($matches); $j < $tot; $j++)
			{
				$matches[$j][0] = str_replace(array(LD,RD), '', $matches[$j][0]);

				$this->date_vars[$matches[$j][1]][$matches[$j][0]] = $matches[$j][2];
			}
		}
		else
		{
			// make sure we don't try to parse date variables again on further calls to parse_variables() or parse_variables_row()
			$this->date_vars = FALSE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Parses {switch=} variables in a row of tag data
	 *
	 * @param	string	Tag data for current row being parsed
	 * @param	int		Count of current row to determine which value to
	 *                  replace the switch tag with
	 * @param	string	Optional tag prefix
	 * @return	string	Tag data with parsed switch variables
	 */
	public function parse_switch($tagdata, $count, $prefix = '')
	{
		if (preg_match_all(
			'/'.LD.$prefix.'switch\s*=([\'"])([^\1].+)\1'.RD.'/iU',
			$tagdata,
			$matches,
			PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				// Captured parameter
				$options = explode("|", $match[2]);

				$value = $options[($count + count($options)) % count($options)];

				$tagdata = str_replace($match[0], $value, $tagdata);
			}
		}

		return $tagdata;
	}
}
// END CLASS

/* End of file Template.php */
/* Location: ./system/expressionengine/libraries/Template.php */
