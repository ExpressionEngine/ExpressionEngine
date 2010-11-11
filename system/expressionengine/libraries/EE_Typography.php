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
 * ExpressionEngine Core Typography Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Typography extends CI_Typography {

	var $single_line_pgfs			= TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
	var $text_format				= 'xhtml';  // xhtml, br, none, or lite
	var $html_format				= 'safe';	// safe, all, none
	var $auto_links	 				= 'y'; 
	var $allow_img_url  			= 'n';
	var $parse_images				= TRUE;
	var $allow_headings				= TRUE;
	var $encode_email				= TRUE;
	var $encode_type				= 'javascript'; // javascript or noscript
	var $use_span_tags  			= TRUE;
	var $popup_links				= FALSE;
	var $bounce						= '';
	var $smiley_array				= FALSE;
	var $parse_smileys				= TRUE;
	var $highlight_code				= TRUE;
	var $convert_curly				= TRUE;		// Convert Curly Brackets Into Entities
	var $emoticon_path  			= '';
	var $site_index					= '';
	var $word_censor				= FALSE;
	var $censored_words 			= array();
	var $censored_replace			= '';
	var $text_fmt_types				= array('xhtml', 'br', 'none', 'lite');
	var $text_fmt_plugins			= array();
	var $html_fmt_types				= array('safe', 'all', 'none');
	var $yes_no_syntax				= array('y', 'n');
	var $code_chunks				= array();
	var $code_counter				= 0;
	var $http_hidden 				= 'ed9f01a60cc1ac21bf6f1684e5a3be23f38a51b9'; // hash to protect URLs in [url] BBCode
	
	// Allowed tags  Note: Specified in initialize()
	var $safe_encode = array();
	var $safe_decode = array();

	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		$this->EE =& get_instance();
		$this->initialize();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Initialize
	 *
	 * Reset all class properties - call after loading and before use
	 * since CI will return the existing class when it's requested each time
	 * inheriting the previous use's properties
	 *
	 * @access	public
	 * @return	void
	 */
	function initialize($config = array())
	{
		// reset class properties
		$this->single_line_pgfs		= TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
		$this->text_format			= 'xhtml';  // xhtml, br, none, or lite
		$this->html_format			= 'safe';	// safe, all, none
		$this->auto_links	 		= 'y'; 
		$this->allow_img_url  		= 'n';
		$this->parse_images			= TRUE;
		$this->allow_headings		= TRUE;
		$this->encode_email			= TRUE;
		$this->encode_type			= 'javascript'; // javascript or noscript
		$this->use_span_tags  		= TRUE;
		$this->popup_links			= FALSE;
		$this->bounce				= '';
		$this->smiley_array			= FALSE;
		$this->parse_smileys		= TRUE;
		$this->highlight_code		= TRUE;
		$this->convert_curly		= TRUE;		// Convert Curly Brackets Into Entities
		$this->emoticon_path  		= '';
		$this->site_index			= '';
		$this->word_censor			= FALSE;
		$this->censored_words 		= array();
		$this->censored_replace		= '';
		$this->text_fmt_types		= array('xhtml', 'br', 'none', 'lite');
		$this->text_fmt_plugins		= array();
		$this->html_fmt_types		= array('safe', 'all', 'none');
		$this->yes_no_syntax		= array('y', 'n');
		$this->code_chunks			= array();
		$this->code_counter			= 0;
		$this->http_hidden 			= 'ed9f01a60cc1ac21bf6f1684e5a3be23f38a51b9'; // hash to protect URLs in [url] BBCode

		foreach ($config as $key => $val)
		{
			$this->$key = $val;
		}

		/** -------------------------------------
		/**  Allowed tags
		/** -------------------------------------*/

		// Note: The decoding array is associative, allowing more precise mapping

		$this->safe_encode = array('b', 'i', 'em', 'del', 'ins', 'strong', 'pre', 'code', 'blockquote');

		$this->safe_decode = array(
									'b'			 	=> 'b', 
									'i'			 	=> 'i',
									'em'			=> 'em', 
									'del'			=> 'del',
									'ins'			=> 'ins',
									'strong'		=> 'strong', 
									'pre'			=> 'pre', 
									'code'			=> 'code', 
									'blockquote'	=> 'blockquote',
									'quote'			=> 'blockquote',
									'QUOTE'			=> 'blockquote'
								 );
								
		// enable quote protection within braces for EE {variable="attributes"}
		$this->protect_braced_quotes = TRUE;

		if ($this->allow_headings == TRUE)
		{
			foreach (array('h2', 'h3', 'h4', 'h5', 'h6') as $val)
			{
				$this->safe_encode[] = $val;
				$this->safe_decode[$val] = $val;
			}
		}

		/** -------------------------------------
		/**  Fetch emoticon prefs
		/** -------------------------------------*/
		
		if ($this->EE->config->item('enable_emoticons') == 'y')
		{
			if (is_file(PATH_MOD.'emoticon/emoticons'.EXT))
			{
				require PATH_MOD.'emoticon/emoticons'.EXT;
				
				if (is_array($smileys))
				{
					$this->smiley_array = $smileys;
					$this->emoticon_path = $this->EE->config->slash_item('emoticon_path');
				}
			}
		}
		
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- popup_link => Have links created by Typography class open in a new window (y/n)
		/* -------------------------------------------*/
		
		if ($this->EE->config->item('popup_link') !== FALSE)
		{
			$this->popup_links = ($this->EE->config->item('popup_link') == 'y') ? TRUE : FALSE;
		}

		/** -------------------------------------
		/**  Fetch word censoring prefs
		/** -------------------------------------*/
		
		if ($this->EE->config->item('enable_censoring') == 'y')
		{
			$this->word_censor = TRUE;
		}
		
		if ($this->word_censor == TRUE && $this->EE->config->item('censored_words') != '')
		{	
			if ($this->EE->config->item('censor_replacement') !== FALSE)
			{
				$this->censored_replace = $this->EE->config->item('censor_replacement');
			}
			
			$words = str_replace('OR', '|', trim($this->EE->config->item('censored_words')));
	
			if (substr($words, -1) == "|")
			{
				$words = substr($words, 0, -1);
			}
					
			$this->censored_words = explode("|", $words);
			
			if (count($this->censored_words) == 0)
			{
				$this->word_censor = FALSE;
			}
		}
		else
		{
			$this->word_censor = FALSE;
		}
		
		/** -------------------------------------
		/**  Fetch plugins
		/** -------------------------------------*/
		
		$this->EE->load->model('addons_model');
		$this->text_fmt_plugins = $this->EE->addons_model->get_plugin_formatting();
	}

	// --------------------------------------------------------------------
	
	/** ----------------------------------------
	/**  Parse file paths
	/** ----------------------------------------*/
	function parse_file_paths($str)
	{
		if ($this->parse_images == FALSE OR strpos($str, 'filedir_') === FALSE)
		{
			return $str;
		}
		
		foreach ($this->EE->functions->fetch_file_paths() as $key => $val)
		{
			$str = str_replace(array("{filedir_{$key}}", "&#123;filedir_{$key}&#125;"), $val, $str);
		}

		return $str;
	}



	/** -------------------------------------
	/**  Typographic parser
	/** -------------------------------------*/
	
	// Note: The processing order is very important in this function so don't change it!
	
	function parse_type($str, $prefs = '')
	{
		if ($this->parse_images === TRUE)
        {
            $this->file_paths = $this->EE->functions->fetch_file_paths();
        }
        
		// In the future, we might think about caching all of this processing, ya know.
		// Do an md5 of the content, process it, store it, retrieve it, et cetera.
		// Not sure how the clearing of it out would go, and if we stored it in the database
		// that does add yet another query.  Hmmmm.  -Paul

		if ($str == '')
		{
			return;	
		}
		
		// -------------------------------------------
		// 'typography_parse_type_start' hook.
		//  - Modify string prior to all other typography processing
		//
			if ($this->EE->extensions->active_hook('typography_parse_type_start') === TRUE)
			{
				$str = $this->EE->extensions->call('typography_parse_type_start', $str, $this, $prefs);
			}	
		//
		// -------------------------------------------

		/** -------------------------------------
		/**  Encode PHP tags
		/** -------------------------------------*/
		
		// Before we do anything else, we'll convert PHP tags into character entities.
		// This is so that PHP submitted in channel entries, comments, etc. won't get parsed.
		// Since you can enable templates to parse PHP, it would open up a security
		// hole to leave PHP submitted in entries and comments intact.
		
		$this->EE->load->helper('security');
		
		$str = encode_php_tags($str);

		/** -------------------------------------
		/**  Encode EE tags
		/** -------------------------------------*/
		
		// Next, we need to encode EE tags contained in entries, comments, etc. so that they don't get parsed.
				
		$str = $this->EE->functions->encode_ee_tags($str, $this->convert_curly);  
			
		/** -------------------------------------
		/**  Set up our preferences
		/** -------------------------------------*/
		
		if (is_array($prefs))
		{
			if (isset($prefs['text_format']))
			{
				if ($prefs['text_format'] != 'none')
				{
					if (in_array($prefs['text_format'], $this->text_fmt_types))
					{
						$this->text_format = $prefs['text_format'];
					}
					else
					{
						if (isset($this->text_fmt_plugins[$prefs['text_format']]) AND (file_exists(PATH_PI.'pi.'.$prefs['text_format'].EXT) OR file_exists(PATH_THIRD.$prefs['text_format'].'/pi.'.$prefs['text_format'].EXT)))
						{
							$this->text_format = $prefs['text_format'];
						}
					}
				}
				else
				{
					$this->text_format = 'none';
				}
			}
		
			if (isset($prefs['html_format']) AND in_array($prefs['html_format'], $this->html_fmt_types))
			{
				$this->html_format = $prefs['html_format'];
			}
		
			if (isset($prefs['auto_links']) AND in_array($prefs['auto_links'], $this->yes_no_syntax))
			{
				$this->auto_links = $prefs['auto_links'];
			}

			if (isset($prefs['allow_img_url'])  AND in_array($prefs['allow_img_url'], $this->yes_no_syntax))
			{
				$this->allow_img_url = $prefs['allow_img_url'];
			}
		}
		
		/** -------------------------------------
		/**  Are single lines considered paragraphs?
		/** -------------------------------------*/
				
		if ($this->single_line_pgfs != TRUE)
		{
			if ($this->text_format == 'xhtml' AND strpos($str, "\r") === FALSE AND strpos($str, "\n") === FALSE)
			{
				$this->text_format = 'lite';
			}
		}
		
		//  Fix emoticon bug
		$str = str_replace(array('>:-(', '>:('), array(':angry:', ':mad:'), $str);
		
		/** -------------------------------------
		/**  Highlight text within [code] tags
		/** -------------------------------------*/
		
		// If highlighting is enabled, we'll highlight <pre> tags as well.
		
		if ($this->highlight_code == TRUE)
		{
			$str = str_replace(array('[pre]', '[/pre]'), array('[code]', '[/code]'), $str);
		}		
			
		// We don't want BBCode parsed if it's within code examples so we'll convert the brackets
		
		if (strpos($str, '[code]') !== FALSE)
		{
			if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $str, $matches))
			{	  		
				for ($i = 0; $i < count($matches['1']); $i++)
				{				
					$temp = str_replace(array('[', ']'), array('&#91;', '&#93;'), $matches['1'][$i]);
					$str  = str_replace($matches['0'][$i], '[code]'.$temp.'[/code]', $str);
				}			
			}
			
			if ($this->highlight_code == TRUE)
			{
				$str = $this->text_highlight($str);
			}
			else
			{
				$str = str_replace(array('[code]', '[/code]'),	array('<code>', '</code>'),	$str);
			}
		}	

		//  Strip IMG tags if not allowed
		if ($this->allow_img_url == 'n')
		{
			$str = $this->strip_images($str);
		}

		//  Format HTML
		$str = $this->format_html($str);

		//  Auto-link URLs and email addresses
		if ($this->auto_links == 'y' AND $this->html_format != 'none')
		{
			$str = $this->auto_linker($str);
		}
		
		//  Parse file paths (in images)
		$str = $this->parse_file_paths($str);

		/** ---------------------------------------
		/**  Convert HTML links in CP to BBCode
		/** ---------------------------------------*/
		
		// Forces HTML links output in the control panel to BBCode so they will be formatted
		// as redirects, to prevent the control panel address from showing up in referrer logs
		// except when sending emails, where we don't want created links piped through the site

		if (REQ == 'CP' && $this->EE->input->get('M') != 'send_email' && strpos($str, 'href=') !== FALSE)
		{
			$str = preg_replace("#<a\s+(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);
		}
		

		//  Decode BBCode
		$str = $this->decode_bbcode($str);

		/** -------------------------------------
		/**  Format text
		/** -------------------------------------*/
		switch ($this->text_format)
		{
			case 'none';
				break;
			case 'xhtml'	: $str = $this->xhtml_typography($str);
				break;
			case 'lite'		: $str = $this->format_characters($str);  // Used with channel entry titles
				break;
			case 'br'		: $str = $this->nl2br_except_pre($str);
				break;
			default			:
			
			if ( ! class_exists('EE_Template'))
			{
				require APPPATH.'libraries/Template'.EXT;
				$this->EE->TMPL = new EE_Template();
			}			
			
			$plugin = ucfirst($prefs['text_format']);
			
			if ( ! class_exists($plugin))
			{	
				if (in_array($prefs['text_format'], $this->EE->core->native_plugins))
				{
					require_once PATH_PI.'pi.'.$prefs['text_format'].EXT;
				}
				else
				{
					require_once PATH_THIRD.$prefs['text_format'].'/pi.'.$prefs['text_format'].EXT;
				}
			}
			
			if (class_exists($plugin))
			{
				$PLG = new $plugin($str);
			
				if (isset($PLG->return_data))
				{
					$str = $PLG->return_data;
				}
			}
			
				break;
		}
		

		//  Parse emoticons
		$str = $this->emoticon_replace($str);
		
		//  Parse censored words
		if ($this->word_censor === TRUE && count($this->censored_words > 0))
		{
			$this->EE->load->helper('text');
			$str = word_censor($str, $this->censored_words, $this->censored_replace);			
		}

		/** ------------------------------------------
		/**  Decode and spam-protect email addresses
		/** ------------------------------------------*/
		
		// {encode="you@yoursite.com" title="Click Me"}
		
		// Note: We only do this here if it's a CP request since the
		// template parser handles this for page requets
		
		if (REQ == 'CP' && strpos($str, '{encode=') !== FALSE)
		{
			if (preg_match_all("/\{encode=(.+?)\}/i", $str, $matches))
			{	
				for ($j = 0; $j < count($matches['0']); $j++)
				{	
					$str = str_replace($matches['0'][$j], $this->EE->functions->encode_email($matches['1'][$j]), $str);
				}
			}  		
		}
		
		// Standard email addresses
		
		$str = $this->decode_emails($str);
		
		/** ------------------------------------------
		/**  Insert the cached code tags
		/** ------------------------------------------*/
		
		// The hightlight function called earlier converts the original code strings into markers
		// so that the auth_xhtml function doesn't attempt to process the highlighted code chunks.
		// Here we convert the markers back to their correct state.
		
		if (count($this->code_chunks) > 0)
		{
			foreach ($this->code_chunks as $key => $val)
			{
				if ($this->text_format == 'legacy_typography')
				{
					// First line takes care of the line break that might be there, which should
					// be a line break because it is just a simple break from the [code] tag.
					$str = str_replace('<div class="codeblock">{'.$key.'yH45k02wsSdrp}</div>'."\n<br />", '</p><div class="codeblock">'.$val.'</div><p>', $str);
					$str = str_replace('<div class="codeblock">{'.$key.'yH45k02wsSdrp}</div>', '</p><div class="codeblock">'.$val.'</div><p>', $str);
				}
				else
				{
					$str = str_replace('{'.$key.'yH45k02wsSdrp}', $val, $str);
				}
			}
			
			$this->code_chunks = array();
		}
		
		// -------------------------------------------
		// 'typography_parse_type_end' hook.
		//  - Modify string after all other typography processing
		//
			if ($this->EE->extensions->active_hook('typography_parse_type_end') === TRUE)
			{
				$str = $this->EE->extensions->call('typography_parse_type_end', $str, $this, $prefs);
			}	
		//
		// -------------------------------------------

		return $str;
	}



	/** -------------------------------------
	/**  Format HTML
	/** -------------------------------------*/
	function format_html($str)
	{
		$html_options = array('all', 'safe', 'none');
	
		if ( ! in_array($this->html_format, $html_options))
		{
			$this->html_format = 'safe';
		}	
	
		if ($this->html_format == 'all')
		{
			return $str;
		}

		if ($this->html_format == 'none')
		{
			return $this->encode_tags($str);
		}
	
		/** -------------------------------------
		/**  Permit only safe HTML
		/** -------------------------------------*/
		
		$str = $this->EE->security->xss_clean($str);
		
		// We strip any JavaScript event handlers from image links or anchors
		// This prevents cross-site scripting hacks.
		
	 	$js = array(	
						'onblur',
						'onchange',
						'onclick',
						'onfocus',
						'onload',
						'onmouseover',
						'onmouseup',
						'onmousedown',
						'onselect',
						'onsubmit',
						'onunload',
						'onkeypress',
						'onkeydown',
						'onkeyup',
						'onresize'
					);
		
		
		foreach ($js as $val)
		{
			if (stristr($str, $val) !== FALSE)
			{
				$str = preg_replace("/<img src\s*=(.+?)".$val."\s*\=.+?\>/i", "<img src=\\1 />", $str);
				$str = preg_replace("/<a href\s*=(.+?)".$val."\s*\=.+?\>/i", "<a href=\\1>", $str);
			}
		}		
		
		// Turn <br /> tags into newlines
		
		if (stristr($str, '<br') !== FALSE)
		{
			$str = preg_replace("#<br>|<br />#i", "\n", $str);
		}
		
		// Strip paragraph tags
		
		if (stristr($str, '<p') !== FALSE)
		{
			$str = preg_replace("#<(/)?pre[^>]*?>#i", "<$1pre>", $str);
			$str = preg_replace("#<p>|<p(?!re)[^>]*?".">|</p>#i", "",  preg_replace("#<\/p><p(?!re)[^>]*?".">#i", "\n", $str)); 
		}
		
		// Convert allowed HTML to BBCode
		
		foreach($this->safe_encode as $val)
		{
			if (stristr($str, $val.'>') !== FALSE)
			{
				$str = preg_replace("#<".$val.">(.+?)</".$val.">#si", "[$val]\\1[/$val]", $str);
			}
		}

		// Convert codeblock division used with code tag

		if (count($this->code_chunks) > 0)
		{
			foreach ($this->code_chunks as $key => $val)
			{
				$str = str_replace('<div class="codeblock">{'.$key.'yH45k02wsSdrp}</div>', '[div class="codeblock"]{'.$key.'yH45k02wsSdrp}[/div]', $str);
			}
 		}

		// Convert anchors to BBCode
        // We do this to prevent allowed HTML from getting converted in the next step
        // Old method would only convert links that had href= as the first tag attribute
		// $str = preg_replace("#<a\s+href=[\"'](\S+?)[\"'](.*?)\>(.*?)</a>#si", "[url=\"\\1\"\\2]\\3[/url]", $str);
		
		if (stristr($str, '<a') !== FALSE)
		{
			$str = preg_replace("#<a\s+(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);
		}
		
		// Convert image tags BBCode

		$str = str_replace("/>", ">", $str);
		
		if (stristr($str, '<img') !== FALSE)
		{
			$str = preg_replace("#<img(.*?)src=\s*[\"'](.+?)[\"'](.*?)\s*\>#si", "[img]\\2\\3\\1[/img]", $str);
		}
		
		if (stristr($str, '://') !== FALSE)
		{
			$str = preg_replace( "#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)\.(jpg|jpeg|gif|png)#i", "\\1[img]http\\4://\\5\\6.\\7[/img]", $str);
		}
		
		return $this->encode_tags($str);
	}

	/** -------------------------------------
	/**  Auto link URLs and email addresses
	/** -------------------------------------*/
	function auto_linker($str)
	{
		$str .= ' ';
		
		// We don't want any links that appear in the control panel (in channel entries, comments, etc.)
		// to point directly at URLs.  Why?  Becuase the control panel URL will end up in people's referrer logs, 
		// This would be a bad thing.  So, we'll point all links to the "bounce server"

		$this->bounce = ((REQ == 'CP' && $this->EE->input->get('M') != 'send_email') OR $this->EE->config->item('redirect_submitted_links') == 'y') ? $this->EE->functions->fetch_site_index().QUERY_MARKER.'URL=' : '';
		
		// Protect URLs that are already in [url] BBCode
		if (strpos($str, '[url') !== FALSE)
		{
			$str = preg_replace("/(\[url[^\]]*?\])http/is", '${1}'.$this->http_hidden, str_replace('[url=http', '[url='.$this->http_hidden, $str));
		}
		
		// New version.  Blame Paul if it doesn't work
		// The parentheses on the end attempt to call any content after the URL. 
		// This way we can make sure it is not [url=http://site.com]http://site.com[/url]
		if (strpos($str, 'http') !== FALSE)
		{
			$str = preg_replace_callback("#(^|\s|\(|..\])((http(s?)://)|(www\.))(\w+[^\s\)\<\[]+)#im", array(&$this, 'auto_linker_callback'), $str);
		}
		
		// Auto link email
		if (strpos($str, '@') !== FALSE)
		{
			$str = preg_replace("/(^|\s|\(|\>)([a-zA-Z0-9_\.\-]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", "\\1[email]\\2@\\3.\\4[/email]", $str);
		}
		
		// Clear period(s) from the end of emails
		if (strpos($str, 'email]') !== FALSE)
		{
			$str = preg_replace("|(\.+)\[\/email\]|i ", "[/email]\\1", $str);
		}
		
		// UnProtect URLs that are already in [url] BBCode
		$str = str_replace($this->http_hidden, 'http', $str);
 
		return substr($str, 0, -1);  // Removes space added above
	}

	
	/** -------------------------------------
	/**  Callback function used above
	/** -------------------------------------*/
	function auto_linker_callback($matches)
	{
		//  If it is in BBCode, then we do not auto link
		if (strtolower($matches['1']) == 'mg]' OR 
			strtolower($matches['1']) == 'rl]' OR
			strtolower(substr(trim($matches[6]), 0, 6)) == '[/url]'
			)
		{
			return $matches['0'];
		}
		
		/** -----------------------------------
		/**  Moved the Comment and Period Modification Here
		/** -----------------------------------*/
		
		$end = '';
		
		if (preg_match("/^(.+?)([\.\,]+)$/",$matches['6'], $punc_match))
		{
			$end = $punc_match[2];
			$matches[6] = $punc_match[1];
		}
		
		/** -----------------------------------
		/**  Modified 2006-02-07 to send back BBCode instead of HTML.  Insures correct sanitizing.
		/** -----------------------------------*/
		
		return	$matches['1'].'[url=http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].']http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].'[/url]'.
				$end;
		
		/** -----------------------------------
		/**  Old Way
		/** -----------------------------------*/
		
		$url_core = (REQ == 'CP' OR $this->EE->config->item('redirect_submitted_links') == 'y') ? urlencode($matches['6']) : $matches['6'];

		return	$matches['1'].'<a href="'.$this->bounce.'http'.
				$matches['4'].'://'.
				$matches['5'].
				$url_core.'"'.(($this->popup_links == TRUE) ? ' onclick="window.open(this.href); return false;" ' : '').'>http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].'</a>'.
				$end;
	}



	/** -------------------------------------
	/**  Decode BBCode
	/** -------------------------------------*/
	function decode_bbcode($str)
	{
		/** -------------------------------------
        /**  Remap some deprecated tags with valid counterparts
        /** -------------------------------------*/
		
		$str = str_replace(array('[strike]', '[/strike]', '[u]', '[/u]'), array('[del]', '[/del]', '[em]', '[/em]'), $str);
		
		/** -------------------------------------
		/**  Decode BBCode array map 
		/** -------------------------------------*/
				
		foreach($this->safe_decode as $key => $val)
		{
			$str = str_replace(array('['.$key.']', '[/'.$key.']'),	array('<'.$val.'>', '</'.$val.'>'),	$str);
		}
		
		/** -------------------------------------
        /**  Decode codeblock division for code tag
        /** -------------------------------------*/

		if (count($this->code_chunks) > 0)
		{
			foreach ($this->code_chunks as $key => $val)
			{
				$str = str_replace('[div class="codeblock"]{'.$key.'yH45k02wsSdrp}[/div]', '<div class="codeblock">{'.$key.'yH45k02wsSdrp}</div>', $str);
			}
 		}

		/** -------------------------------------
		/**  Decode color tags
		/** -------------------------------------*/
		
		if (strpos($str, '[color=') !== FALSE)
		{
			if ($this->use_span_tags == TRUE)
			{
				$str = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/si", "<span style=\"color:\\1;\">\\2</span>",$str);
			}	
			else
			{
				$str = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/si", "<font color=\"\\1\">\\2</font>", $str);
			}
		}
		
		/** -------------------------------------
		/**  Decode size tags
		/** -------------------------------------*/
		
		if (strpos($str, '[size=') !== FALSE)
		{
			if ($this->use_span_tags == TRUE)
			{
				$str = preg_replace_callback("/\[size=(.*?)\](.*?)\[\/size\]/si", array($this, "font_matrix"),$str);
			}	
			else
			{
				$str = preg_replace("/\[size=(.*?)\](.*?)\[\/size\]/si", "<font color=\"\\1\">\\2</font>", $str);
			}
		}
		
		/** -------------------------------------
		/**  Convert [url] tags to links 
		/** -------------------------------------*/
		
		if (strpos($str, '[url') !== FALSE)
		{			
			$bounce	= ((REQ == 'CP' && $this->EE->input->get('M') != 'send_email') OR $this->EE->config->item('redirect_submitted_links') == 'y') ? $this->EE->functions->fetch_site_index().QUERY_MARKER.'URL=' : '';

			$bad_things	 = array("'",'"', ';', '[', '(', ')', '!', '*', '>', '<', "\t", "\r", "\n", 'document.cookie'); // everything else
			$bad_things2 = array('[', '(', ')', '!', '*', '>', '<', "\t", 'document.cookie'); // style,title attributes
			$exceptions	 = array('http://', 'https://', 'irc://', 'feed://', 'ftp://', 'ftps://', 'mailto:', '/', '#');
			$allowed	 = array('rel', 'title', 'class', 'style', 'target');

			if (preg_match_all("/\[url(.*?)\](.*?)\[\/url\]/i", $str, $matches))
			{
				for($i=0, $s=count($matches['0']), $add=TRUE; $i < $s; ++$i)
				{
					$matches['1'][$i] = trim($matches['1'][$i]);
					
					$url = ($matches['1'][$i] != '') ? trim($matches['1'][$i]) : $matches['2'][$i];
					$extra = '';
					
					// remove all attributes except for the href in "Safe" HTML formatting
					// Also force links output in the CP with the Typography class as "safe" so that
					// any other tag attributes that it might have are not slapped in with the URL
					if (($this->html_format == 'safe' OR REQ == 'CP') && stristr($matches['1'][$i],' '))
					{
						for($a=0, $sa=count($allowed); $a < $sa; ++$a)
						{
							if (($p1 = strpos($url, $allowed[$a].'=')) !== FALSE)
							{
								$marker = substr($url, $p1 + strlen($allowed[$a].'='), 1);
								
								if ($marker != "'" && $marker != '"') continue;
								
								$p2	= strpos(substr($url, $p1 + strlen($allowed[$a].'=') + 1), $marker);
								
								if ($p2 === FALSE) continue;
								
								// Do not make me explain the math here, it gives me a headache - Paul
								
								$inside = str_replace((($allowed[$a] == 'style' OR $allowed[$a] == 'title') ? $bad_things2 : $bad_things), 
													  '', 
													  substr($url, $p1 + strlen($allowed[$a].'=') + 1, $p2));
													  
								$extra .= ' '.$allowed[$a].'='.$marker.$inside.$marker;
							}
						}
						
						// remove everything but the URL up to the first space
						$url = substr($url, 0, strpos($url, ' '));
						
						// get rid of opening = and surrounding quotes
						$url = preg_replace(array('/^=(\042|\047)?/', '/(\042|\047)$/'), '', $url);

						// url encode a few characters that we want to allow, in the wiki for example
						$url = str_replace(array('"', "'", '!'), array('%22', '%27', '%21'), $url);
	        		}
					else
					{
						// If allowed is none- we ditch everything but the url
						if ($this->html_format == 'none' && ($space_pos = strpos($url, ' ')) !== FALSE)
						{
							$url = substr($url, 0, $space_pos);
						}
						
						
						// get rid of opening = and surrounding quotes (again for allow all!)
						$url = preg_replace(array('/^=(\042|\047)?/', '/(\042|\047)$/'), '', $url);	
					}

					// Clean out naughty stuff from URL.
					$url = ($this->html_format == 'all') ? str_replace($bad_things2, '', $url) : str_replace($bad_things, '', $url);
					
					$add = TRUE;
					
					foreach($exceptions as $exception)
					{
						if (substr($url, 0, strlen($exception)) == $exception)
						{
							$add = FALSE; break;
						}
					}
				
					if ($add === TRUE)
					{
						$url = "http://".$url;
					}
					
					$extra .= (($this->popup_links == TRUE) ? ' onclick="window.open(this.href); return false;" ' : '');
					
					if ($bounce != '')
					{
						$url = urlencode($url);
					}
					
					$str = str_replace($matches['0'][$i], '<a href="'.$bounce.trim($url).'"'.$extra.'>'.$matches['2'][$i]."</a>", $str);
				}
			}
		}

		/** -------------------------------------
		/**  Image tags
		/** -------------------------------------*/
		// [img] and [/img]
		
		if (strpos($str, '[img]') !== FALSE)
		{
			$bad_things	 = array("'",'"', ';', '[', '(', ')', '!', '*', '>', '<', "\t", "\r", "\n", 'document.cookie');

			if ($this->allow_img_url == 'y')
			{	
				$str = preg_replace_callback("/\[img\](.*?)\[\/img\]/i", array($this, "image_sanitize"), $str); 
				//$str = preg_replace("/\[img\](.*?)\[\/img\]/i", "<img src=\\1 />", $str);
			}
			elseif($this->auto_links == 'y' && $this->html_format != 'none')
			{
				if (preg_match_all("/\[img\](.*?)\[\/img\]/i", $str, $matches))
				{
					for($i=0, $s=count($matches['0']); $i < $s; ++$i)
					{
						$str = str_replace($matches['0'][$i], '<a href="'.str_replace($bad_things, '', $matches['1'][$i]).'">'.str_replace($bad_things, '', $matches['1'][$i])."</a>", $str);
					}
				}					
			}
			else
			{
				$str = preg_replace("/\[img\](.*?)\[\/img\]/i", "\\1", $str);
			}
		}
		
		// Add quotes back to image tag if missing
		
		if (strpos($str, '<img src=') !== FALSE)
		{
			$str = preg_replace("/<img src=([^\"\'\s]+)(.*?)\/\>/i", "<img src=\"\\1\" \\2/>", $str);
		}
		
		/** -------------------------------------
		/**  Style tags
		/** -------------------------------------*/
		
		// [style=class_name]stuff..[/style]  
	
		if (strpos($str, '[style=') !== FALSE)
		{
			$str = preg_replace("/\[style=(.*?)\](.*?)\[\/style\]/si", "<span class=\"\\1\">\\2</span>", $str);	
		}
		
		/** ---------------------------------------
		/**  Attributed quotes, used in the Forum module
		/** ---------------------------------------*/

		// [quote author="Brett" date="11231189803874"]...[/quote]
		
		if (strpos($str, '[quote') !== FALSE)
		{
			$str = preg_replace('/\[quote\s+(author=".*?"\s+date=".*?")\]/si', '<blockquote \\1>', $str);
		}
		
		return $str;
	}

	
	/** -----------------------------------------
	/**  Make images safe
	/** -----------------------------------------*/
	
	// This simply removes parenthesis so that javascript event handlers
	// can't be invoked. 

	function image_sanitize($matches)
	{		
		$url = str_replace(array('(', ')'), '', $matches['1']);

		$width = '';
		$height = '';

		if (preg_match("/\s+width=(\"|\')([^\\1]*?)\\1/", $matches[1], $width_match))
		{
			$url = trim(str_replace($width_match[0], '', $url));
			$width = $width_match[0];
		}

		if (preg_match("/\s+height=(\"|\')([^\\1]*?)\\1/", $matches[1], $height_match))
		{	
			$url = trim(str_replace($height_match[0], '', $url));
			$height = $height_match[0];
		}


		if (preg_match("/\s+alt=(\"|\')([^\\1]*?)\\1/", $matches[1], $alt_match))
		{
			$url = trim(str_replace($alt_match['0'], '', $url));
			$alt = str_replace(array('"', "'"), '', $alt_match[2]);
		}
		else
		{
			$alt = str_replace(array('"', "'"), '', $url);
			
			if (substr($alt, -1) == '/')
			{
				$alt = substr($alt, 0, -1);
			}
			
			$alt = substr($alt, strrpos($alt, '/')+1);
		}
		
		return "<img src=\"{$url}\" alt=\"{$alt}\"}{$width}{$height} />";
	}

	
	/** -----------------------------------------
	/**  Decode and spam protect email addresses
	/** -----------------------------------------*/
	function decode_emails($str)
	{
		if (strpos($str, '[email') === FALSE)
		{
			return $str;
		}
		
		// [email=your@yoursite]email[/email]

		$str = preg_replace_callback("/\[email=(.*?)\](.*?)\[\/email\]/i", array($this, "create_mailto"),$str);
		
		// [email]joe@xyz.com[/email]

		$str = preg_replace_callback("/\[email\](.*?)\[\/email\]/i", array($this, "create_mailto"),$str);
		
		return $str;
	}

	

	/** -------------------------------------
	/**  Format Email via callback
	/** -------------------------------------*/
	function create_mailto($matches)
	{	
		$title = ( ! isset($matches['2'])) ? $matches['1'] : $matches['2'];
	
		if ($this->encode_email == TRUE)
		{
			return $this->encode_email($matches['1'], $title, TRUE);
		}
		else
		{
			return "<a href=\"mailto:".$matches['1']."\">".$title."</a>";		
		}
	}

	

	/** ----------------------------------------
	/**  Font sizing matrix via callback
	/** ----------------------------------------*/
	function font_matrix($matches)
	{
		switch($matches['1'])
		{
			case 1  : $size = '9px';
				break;
			case 2  : $size = '11px';
				break;
			case 3  : $size = '14px';
				break;
			case 4  : $size = '16px';
				break;
			case 5  : $size = '18px';
				break;
			case 6  : $size = '20px';
				break;
			default : $size = '11px';
				break;
		}
	
		return "<span style=\"font-size:".$size.";\">".$matches['2']."</span>";
	}
	
	
	/** -------------------------------------
	/**  Encode tags
	/** -------------------------------------*/
	
	function encode_tags($str) 
	{  
		return str_replace(array("<", ">"), array("&lt;", "&gt;"), $str);
	}

	/** -------------------------------------
	/**  Strip IMG tags
	/** -------------------------------------*/
	function strip_images($str)
	{	
		if (strpos($str, '<img') !== FALSE)
		{
			$str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
			$str = preg_replace("#<img\s+.*?src\s*=\s*(.+?)\s*\>#", "\\1", $str);
		}
		
		return $str;
	}

	/** -------------------------------------
	/**  Emoticon replacement
	/** -------------------------------------*/
	function emoticon_replace($str)
	{
		if ($this->smiley_array === FALSE OR $this->parse_smileys === FALSE OR $this->EE->session->userdata('parse_smileys') == 'n')
		{
			return $str;
		}
		
		$counter = 0;
		
		// Find any code and pre tags to exclude
		if (strpos($str, '<pre>') !== FALSE OR strpos($str, '<code>') !== FALSE)
		{
			if (preg_match_all("/(<pre>(.+?)<\/pre>)|(<code>(.+?)<\/code>)/si", $str, $matches))
			{
				for ($counter = 0, $total = count($matches[0]); $counter < $total; $counter++)
				{
					$code_chunk[$counter] = $matches[0][$counter];
					$str = str_replace($matches[0][$counter], '{'.$counter.'xyH45k02wsSdrp}', $str);
				}
			}
		}

		$str = ' '.$str;
		
		foreach ($this->smiley_array as $key => $val)
		{
			if (strpos($str, $key) !== FALSE)
			{
				$img = "<img src=\"".$this->emoticon_path.$this->smiley_array[$key]['0']."\" width=\"".$this->smiley_array[$key]['1']."\" height=\"".$this->smiley_array[$key]['2']."\" alt=\"".$this->smiley_array[$key]['3']."\" style=\"border:0;\" />";
			
				foreach(array(' ', "\t", "\n", "\r", '.', ',', '>') as $char)
				{
					$str = str_replace($char.$key, $char.$img, $str);
				}
			}
		}
		
        // Flip code chunks back in
		if ($counter > 0)
		{
			foreach ($code_chunk as $key => $val)
			{
				$str = str_replace('{'.$key.'xyH45k02wsSdrp}', $val, $str);
			}
 		}

		return ltrim($str);
	}




	/** -------------------------------------
	/**  Word censor
	/** -------------------------------------*/
	function filter_censored_words($str)
	{
        if ($this->word_censor == FALSE)
        {
            return $str;    
        }
		
		$this->EE->load->helper('text');
		return word_censor($str, $this->censored_words, $this->censored_replace);
	}




	/** -------------------------------------
	/**  Colorize code strings
	/** -------------------------------------*/
		
	function text_highlight($str)
	{		
		// No [code] tags?  No reason to live.  Goodbye cruel world...
		
		if ( ! preg_match_all("/\[code\](.+?)\[\/code\]/si", $str, $matches))
		{	  
			return $str;
		}
		
		for ($i = 0; $i < count($matches['1']); $i++)
		{
			$temp = trim($matches['1'][$i]);
			//$temp = $this->decode_bbcode(trim($matches['1'][$i]));
			
			// Turn <entities> back to ascii.  The highlight string function
			// encodes and highlight brackets so we need them to start raw 
			
			$temp = str_replace(array('&lt;', '&gt;'), array('<', '>'), $temp);
			
			// Replace any existing PHP tags to temporary markers so they don't accidentally
			// break the string out of PHP, and thus, thwart the highlighting.
			// While we're at it, convert EE braces
			
			$temp = str_replace(array('<?', '?'.'>', '{', '}', '&#123;', '&#125;', '&#91;', '&#93;', '\\', '&#40;', '&#41;', '</script>'), 
									  array('phptagopen', 'phptagclose', 'braceopen', 'braceclose', 'braceopen', 'braceclose', 'bracketopen', 'bracketeclose', 'backslashtmp', 'parenthesisopen', 'parenthesisclose', 'scriptclose'), 
									  $temp);
				
			
			// The highlight_string function requires that the text be surrounded
			// by PHP tags, which we will remove later
			$temp = '<?php '.$temp.' ?>'; // <?

			// All the magic happens here, baby!	
			$temp = highlight_string($temp, TRUE);

			// Prior to PHP 5, the highligh function used icky <font> tags
			// so we'll replace them with <span> tags.

			if (abs(PHP_VERSION) < 5)
			{
				$temp = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $temp);
				$temp = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $temp);
			}
			
			// Remove our artificially added PHP, and the syntax highlighting that came with it
			$temp = preg_replace('/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i', '<span style="color: #$1">', $temp);
			$temp = preg_replace('/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is', "$1</span>\n</span>\n</code>", $temp);
			$temp = preg_replace('/<span style="color: #[A-Z0-9]+"\><\/span>/i', '', $temp);
			
			// Replace our markers back to PHP tags.

			$temp = str_replace(array('phptagopen', 'phptagclose', 'braceopen', 'braceclose', 'bracketopen', 'bracketeclose', 'backslashtmp', 'parenthesisopen', 'parenthesisclose', 'scriptclose'), 
									  array('&lt;?', '?&gt;', '&#123;', '&#125;', '&#91;', '&#93;', '\\', '&#40;', '&#41;', '&lt;/script&gt;'), 
									  $temp); //<?
	
			// Cache the code chunk and insert a marker into the original string.
			// we do this so that the auth_xhtml function which gets called later
			// doesn't process our new code chunk
						
			$this->code_chunks[$this->code_counter] = $temp;

			$str = str_replace($matches['0'][$i], '<div class="codeblock">{'.$this->code_counter.'yH45k02wsSdrp}</div>', $str);
			
			$this->code_counter++;
		}		

		return $str;
	}

	


	/** -------------------------------------
	/**  NL to <br /> - Except within <pre>
	/** -------------------------------------*/
	
	function nl2br_except_pre($str)
	{
		$ex = explode("pre>",$str);
		$ct = count($ex);
		
		$newstr = "";
		
		for ($i = 0; $i < $ct; $i++)
		{
			if (($i % 2) == 0)
				$newstr .= nl2br($ex[$i]);
			else 
				$newstr .= $ex[$i];
			
			if ($ct - 1 != $i) 
				$newstr .= "pre>";
		}
		
		return $newstr;
	}


	/** -------------------------------------
	/**  Convert ampersands to entities
	/** -------------------------------------*/
	function convert_ampersands($str)
	{
		if (strpos($str, '&') === FALSE) return $str;
	
		$str = preg_replace("/&#(\d+);/", "AMP14TX903DVGHY4QW\\1;", $str);
		$str = preg_replace("/&(\w+);/",  "AMP14TX903DVGHY4QT\\1;", $str);
		
		return str_replace(array("&","AMP14TX903DVGHY4QW","AMP14TX903DVGHY4QT"),array("&amp;", "&#","&"), $str);
	}


	/** -------------------------------------------
	/**  Auto XHTML Typography - light version
	/** -------------------------------------------*/
	
	// We use this for channel entry titles.  It allows us to 
	// format only the various characters without adding <p> tags
	// Deprecated 9/11/08, format_characters() performs the same
	// action, but with greater accuracy	
	function light_xhtml_typography($str)
	{
		return $this->format_characters($str);
	}
	

	/** -------------------------------------
	/**  Auto XHTML Typography
	/** -------------------------------------*/
    function xhtml_typography($str)
    {  		
		return $this->auto_typography($str);
    }


	/** -------------------------------------
	/**  Encode Email Address
	/** -------------------------------------*/
	function encode_email($email, $title = '', $anchor = TRUE)
	{
		if (isset($this->EE->TMPL) && is_object($this->EE->TMPL) AND isset($this->EE->TMPL->encode_email) AND $this->EE->TMPL->encode_email == FALSE)
		{
			return $email;
		}
	
		if ($title == "")
			$title = $email;
		
		if (isset($this->encode_type) AND $this->encode_type == 'noscript')
		{
			$email = str_replace(array('@', '.'), array(' '.$this->EE->lang->line('at').' ', ' '.$this->EE->lang->line('dot').' '), $email);
			return $email;
		}
		
		$bit = array();
		
		if ($anchor == TRUE)
		{ 
			$bit[] = '<'; $bit[] = 'a '; $bit[] = 'h'; $bit[] = 'r'; $bit[] = 'e'; $bit[] = 'f'; $bit[] = '='; $bit[] = '\"'; $bit[] = 'm'; $bit[] = 'a'; $bit[] = 'i'; $bit[] = 'l';  $bit[] = 't'; $bit[] = 'o'; $bit[] = ':';
		}
		
		for ($i = 0; $i < strlen($email); $i++)
		{
			$bit[] .= " ".ord(substr($email, $i, 1));
		}
		
		$temp	= array();
		
		if ($anchor == TRUE)
		{		
			$bit[] = '\"'; $bit[] = '>';
			
			for ($i = 0; $i < strlen($title); $i++)
			{
				$ordinal = ord($title[$i]);
			
				if ($ordinal < 128)
				{
					$bit[] = " ".$ordinal;			
				}
				else
				{
					if (count($temp) == 0)
					{
						$count = ($ordinal < 224) ? 2 : 3;
					}
				
					$temp[] = $ordinal;
				
					if (count($temp) == $count)
					{
						$number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);
		
						$bit[] = " ".$number;
						$count = 1;
						$temp = array();
					}	
				}
			}
			
			$bit[] = '<'; $bit[] = '/'; $bit[] = 'a'; $bit[] = '>';
		}
		
		$bit = array_reverse($bit);
		$span_id = 'eeEncEmail_'.$this->EE->functions->random('alpha', 10);

		ob_start();
		
?>
<span id='<?php echo $span_id; ?>'>.<?php echo $this->EE->lang->line('encoded_email'); ?></span><script type="text/javascript">
/*<![CDATA[*/
var l=new Array();
var output = '';
<?php
	
	$i = 0;
	foreach ($bit as $val)
	{
?>l[<?php echo $i++; ?>]='<?php echo $val; ?>';<?php
	}
?>

for (var i = l.length-1; i >= 0; i=i-1){ 
if (l[i].substring(0, 1) == ' ') output += "&#"+unescape(l[i].substring(1))+";"; 
else output += unescape(l[i]);
}
document.getElementById('<?php echo $span_id; ?>').innerHTML = output;
/*]]>*/
</script><?php

		$buffer = ob_get_contents();
		ob_end_clean(); 

		return str_replace("\n", '', $buffer);		
	}


}
// END CLASS
/* End of file Typography.php */
/* Location: ./system/expressionengine/libraries/Typography.php */