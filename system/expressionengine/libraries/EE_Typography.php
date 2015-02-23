<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use \Michelf\MarkdownExtra;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Typography extends CI_Typography {

	public $single_line_pgfs = TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
	public $text_format      = 'xhtml';  // xhtml, markdown, br, none, or lite
	public $html_format      = 'safe';	// safe, all, none
	public $auto_links       = 'y';
	public $allow_img_url    = 'n';
	public $separate_parser  = FALSE;
	public $parse_images     = TRUE;
	public $allow_headings   = TRUE;
	public $encode_email     = TRUE;
	public $encode_type      = 'javascript'; // javascript or noscript
	public $use_span_tags    = TRUE;
	public $popup_links      = FALSE;
	public $bounce           = '';
	public $smiley_array     = FALSE;
	public $parse_smileys    = TRUE;
	public $highlight_code   = TRUE;
	public $convert_curly    = TRUE;		// Convert Curly Brackets Into Entities
	public $emoticon_url     = '';
	public $site_index       = '';
	public $word_censor      = FALSE;
	public $censored_words   = array();
	public $censored_replace = '';
	public $text_fmt_types   = array('xhtml', 'markdown', 'br', 'none', 'lite');
	public $text_fmt_plugins = array();
	public $html_fmt_types   = array('safe', 'all', 'none');
	public $yes_no_syntax    = array('y', 'n');
	public $code_chunks      = array();
	public $code_counter     = 0;
	public $http_hidden      = NULL; // hash to protect URLs in [url] BBCode
	public $safe_img_src_end = NULL; // hash to mark end of image URLs during sanitizing of image tags

	// Allowed tags  Note: Specified in initialize()
	public $safe_encode      = array();
	public $safe_decode      = array();

	// A marker used to hide quotes in text
	// before it is passed through the parser.
	private $quote_marker    = NULL;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->initialize();
		Autoloader::getInstance()->addPrefix('Michelf', APPPATH.'libraries/typography/Markdown/Michelf/');
	}

	// --------------------------------------------------------------------

	/**
	 * __set magic method
	 *
	 * Handles writing directly to the class properties
	 *
	 * @param	string
	 * @param	mixed
	 * @return	void
	 */
	public function __set($var, $val)
	{
		if (property_exists($this, $var))
		{
			$this->$var = $val;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize
	 *
	 * Reset all class properties - call after loading and before use
	 * since CI will return the existing class when it's requested each time
	 * inheriting the previous use's properties
	 *
	 * @return	void
	 */
	public function initialize($config = array())
	{
		// reset class properties
		$this->single_line_pgfs = TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
		$this->text_format      = 'xhtml';  // xhtml, markdown, br, none, or lite
		$this->html_format      = 'safe';	// safe, all, none
		$this->auto_links       = 'y';
		$this->allow_img_url    = 'n';
		$this->separate_parser  = FALSE;
		$this->parse_images     = TRUE;
		$this->allow_headings   = TRUE;
		$this->encode_email     = TRUE;
		$this->encode_type      = 'javascript'; // javascript or noscript
		$this->use_span_tags    = TRUE;
		$this->popup_links      = FALSE;
		$this->bounce           = '';
		$this->smiley_array     = FALSE;
		$this->parse_smileys    = TRUE;
		$this->highlight_code   = TRUE;
		$this->convert_curly    = TRUE;		// Convert Curly Brackets Into Entities
		$this->emoticon_url     = '';
		$this->site_index       = '';
		$this->word_censor      = FALSE;
		$this->censored_words   = array();
		$this->censored_replace = '';
		$this->text_fmt_types   = array('xhtml', 'markdown', 'br', 'none', 'lite');
		$this->text_fmt_plugins = array();
		$this->html_fmt_types   = array('safe', 'all', 'none');
		$this->yes_no_syntax    = array('y', 'n');
		$this->code_chunks      = array();
		$this->code_counter     = 0;

		$this->http_hidden      = unique_marker('typography_url_protect'); // hash to protect URLs in [url] BBCode
		$this->safe_img_src_end = unique_marker('typography_img_src_end'); // hash to mark end of image URLs during sanitizing of image tags

		foreach ($config as $key => $val)
		{
			$this->$key = $val;
		}

		/** -------------------------------------
		/**  Allowed tags
		/** -------------------------------------*/

		// Note: The decoding array is associative, allowing more precise mapping

		$this->safe_encode = array(
			'b',
			'i',
			'em',
			'del',
			'ins',
			'strong',
			'pre',
			'code',
			'blockquote',
			'abbr' => array('property' => 'title')
		);

		$this->safe_decode = array(
			'b'          => 'b',
			'i'          => 'i',
			'em'         => 'em',
			'del'        => 'del',
			'ins'        => 'ins',
			'strong'     => 'strong',
			'pre'        => 'pre',
			'code'       => 'code',
			'abbr'       => array('tag' => 'abbr', 'property' => 'title'),
			'blockquote' => 'blockquote',
			'quote'      => 'blockquote',
			'QUOTE'      => 'blockquote'
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

		if (ee()->config->item('enable_emoticons') == 'y')
		{
			$this->_fetch_emotions_prefs();
		}

		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- popup_link => Have links created by Typography class open in a new window (y/n)
		/* -------------------------------------------*/

		if (ee()->config->item('popup_link') !== FALSE)
		{
			$this->popup_links = (ee()->config->item('popup_link') == 'y') ? TRUE : FALSE;
		}

		/** -------------------------------------
		/**  Fetch word censoring prefs
		/** -------------------------------------*/

		if (ee()->config->item('enable_censoring') == 'y')
		{
			$this->_fetch_word_censor_prefs();
		}

		/** -------------------------------------
		/**  Fetch plugins
		/** -------------------------------------*/

		ee()->load->model('addons_model');
		$this->text_fmt_plugins = ee()->addons_model->get_plugin_formatting();
	}

	// --------------------------------------------------------------------

	/**
	 * Parse file paths
	 *
	 * @param 	string
	 */
	public function parse_file_paths($str)
	{
		if ($this->parse_images == FALSE OR strpos($str, 'filedir_') === FALSE)
		{
			return $str;
		}

		ee()->load->library('file_field');
		return ee()->file_field->parse_string($str, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Typographic parser
	 *
	 * Note: The processing order is very important in this function so don't change it!
	 *
	 * @param 	string
	 * @param 	array
	 */
	public function parse_type($str, $prefs = '')
	{
		if ($str == '')
		{
			return;
		}

		// -------------------------------------------
		// 'typography_parse_type_start' hook.
		//  - Modify string prior to all other typography processing
		//
			if (ee()->extensions->active_hook('typography_parse_type_start') === TRUE)
			{
				$str = ee()->extensions->call('typography_parse_type_start', $str, $this, $prefs);
			}
		//
		// -------------------------------------------

		// Set up preferences
		$this->_set_preferences($prefs);

		// Parser-specific pre_process
		if ($this->separate_parser
			&& method_exists($this, $this->text_format.'_pre_process'))
		{
			$str = $this->{$this->text_format.'_pre_process'}($str);
		}

		// Handle single line paragraphs
		if ($this->single_line_pgfs != TRUE)
		{
			if ($this->text_format == 'xhtml' AND strpos($str, "\r") === FALSE AND strpos($str, "\n") === FALSE)
			{
				$this->text_format = 'lite';
			}
		}

		//  Fix emoticon bug
		$str = str_replace(array('>:-(', '>:('), array(':angry:', ':mad:'), $str);


		//  Highlight text within [code] tags
		// If highlighting is enabled, we'll highlight <pre> tags as well.
		if ($this->highlight_code == TRUE)
		{
			$str = str_replace(array('[pre]', '[/pre]'), array('[code]', '[/code]'), $str);
		}

		// We don't want BBCode parsed if it's within code examples so we'll
		// convert the brackets
		$str = $this->_protect_bbcode($str);

		//  Strip IMG tags if not allowed
		if ($this->allow_img_url == 'n')
		{
			$str = $this->strip_images($str);
		}

		//  Format HTML
		$str = $this->format_html($str);

		//  Auto-link URLs and email addresses
		if ($this->auto_links == 'y' && ! $this->separate_parser)
		{
			$str = $this->auto_linker($str);
		}

		//  Parse file paths (in images)
		$str = $this->parse_file_paths($str);

		// Convert HTML links in CP to BBCode
		//
		// Forces HTML links output in the control panel to BBCode so they will
		// be formatted as redirects, to prevent the control panel address from
		// showing up in referrer logs except when sending emails, where we
		// don't want created links piped through the site
		if (REQ == 'CP' && ee()->input->get('M') != 'send_email' && strpos($str, 'href=') !== FALSE)
		{
			$str = preg_replace("#<a\s+(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);
		}

		//  Decode BBCode
		$str = $this->decode_bbcode($str);

		// Format text
		switch ($this->text_format)
		{
			case 'none';
				break;
			case 'xhtml':
				$str = $this->auto_typography($str);
				break;
			case 'markdown':
				$str = $this->markdown($str, $prefs);
				break;
			case 'lite':
				// Used with channel entry titles
				$str = $this->format_characters($str);
				break;
			case 'br':
				$str = $this->nl2br_except_pre($str);
				break;
			default:
				// Plugin of some sort
				$str = $this->parse_plugin($str);
				break;
		}

		//  Parse emoticons
		$str = $this->emoticon_replace($str);

		//  Parse censored words
		if ($this->word_censor === TRUE && count($this->censored_words > 0))
		{
			ee()->load->helper('text');
			$str = word_censor($str, $this->censored_words, $this->censored_replace);
		}

		// Decode {encode=...} only in the CP since the template parser handles
		// this for page requets
		if (REQ == 'CP' && strpos($str, '{encode=') !== FALSE)
		{
			ee()->load->library('template', NULL, 'TMPL');
			$str = ee()->TMPL->parse_encode_email($str);
		}

		// Standard email addresses
		$str = $this->decode_emails($str);

		// Insert the cached code tags
		$str = $this->_convert_code_markers($str);

		// -------------------------------------------
		// 'typography_parse_type_end' hook.
		//  - Modify string after all other typography processing
		//
			if (ee()->extensions->active_hook('typography_parse_type_end') === TRUE)
			{
				$str = ee()->extensions->call('typography_parse_type_end', $str, $this, $prefs);
			}
		//
		// -------------------------------------------

		// Encode PHP Tags
		ee()->load->helper('security');
		$str = encode_php_tags($str);

		// Encode EE Tags
		$str = ee()->functions->encode_ee_tags($str, $this->convert_curly);

		return $str;
	}

	// -------------------------------------------------------------------------

	/**
	 * Set up preferences for parse_type()
	 * @param Array $prefs Array of preferences
	 * @return void
	 */
	private function _set_preferences($prefs)
	{
		if (is_array($prefs))
		{
			if (isset($prefs['text_format']))
			{
				if ($prefs['text_format'] == 'none')
				{
					$this->text_format = 'none';
				}
				else
				{
					if (in_array($prefs['text_format'], $this->text_fmt_types))
					{
						$this->text_format = $prefs['text_format'];
					}
					else
					{
						if (isset($this->text_fmt_plugins[$prefs['text_format']]) &&
							(file_exists(PATH_PI.'pi.'.$prefs['text_format'].'.php') OR
							file_exists(PATH_THIRD.$prefs['text_format'].'/pi.'.$prefs['text_format'].'.php')))
						{
							$this->text_format = $prefs['text_format'];
						}
					}
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

		// If we're dealing with a separate parser (e.g. Markdown)
		$this->separate_parser = ($this->text_format == 'markdown') ? TRUE : FALSE;
	}

	// -------------------------------------------------------------------------

	/**
	 * Parse a generic plugin's contents
	 * @param  String $str String to parse
	 * @return String      Parsed string after going through plugin
	 */
	public function parse_plugin($str)
	{
		if ( ! class_exists('EE_Template'))
		{
			ee()->load->library('template', NULL, 'TMPL');
		}

		$plugin = ucfirst($this->text_format);

		if ( ! class_exists($plugin))
		{
			if (in_array($this->text_format, ee()->core->native_plugins))
			{
				require_once PATH_PI.'pi.'.$this->text_format.'.php';
			}
			else
			{
				require_once PATH_THIRD.$this->text_format.'/pi.'.$this->text_format.'.php';
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

		return $str;
	}

	// -------------------------------------------------------------------------

	/**
	 * Protected Quotes in EE Tags
	 *
	 * Search all EE tags in the string for quotes and protect the quotes from
	 * being parsed by subsequent parsers by replacing them with a marker.  The
	 * marker will then be switched back out for the quotes in question by
	 * running restore quotes in tags.
	 *
	 * Note: The marker is time dependent and stored in the instance of the
	 * typography class, so the call to restore_quotes_in_tags() must be to the
	 * same instance of typography in the same request.
	 *
	 * @param	string	$str	The string potentially containing EE tags that you
	 * 		wish to protect quotes in.
	 *
	 * @return	string	The parsed string with any quotes in EE tags replaced
	 * 		by {{SINGLE_QUOTE:marker}} or {{DOUBLE_QUOTE:marker}} respectively.
	 * 		The marker is time dependent and stored in this instance of the
	 * 		typography object.
	 */
	protected function protect_quotes_in_tags($str)
	{
		if ( ! isset($this->quote_marker) )
		{
			$this->quote_marker = md5(time(0) . 'quote_marker');
		}

		$single_quote_marker = '{{SINGLEQUOTE:' . $this->quote_marker . '}}';
		$double_quote_marker = '{{DOUBLEQUOTE:' . $this->quote_marker . '}}';

		if (preg_match_all("/{.*?}/", $str, $matches, PREG_SET_ORDER))
		{
			foreach($matches as $match)
			{
				$str = str_replace($match[0],
					str_replace(
						array('\'', '"'),
						array($single_quote_marker, $double_quote_marker),
						$match[0]),
					$str
				);
			}
		}

		return $str;
	}

	/**
	 *  Restores Quotes in EE Tags
	 *
	 *  Restores quotes in EE tags hidden by
	 *  EE_Typography::protect_quotes_in_tags().  Must be called on the same
	 *  instance of EE_Typography that protected the quotes, as the marker is
	 *  time dependent and stored on the Typography instance.
	 *
	 *  @param	string	$str	The string in which to restore the quotes.
	 *
	 *  @return string	The string with quotes restored.
	 */
	protected function restore_quotes_in_tags($str)
	{
		$single_quote_marker = '{{SINGLEQUOTE:' . $this->quote_marker . '}}';
		$double_quote_marker = '{{DOUBLEQUOTE:' . $this->quote_marker . '}}';

		return str_replace(array($single_quote_marker, $double_quote_marker), array('\'', '"'), $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Format HTML
	 *
	 * @param string
	 */
	public function format_html($str)
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

		$str = ee()->security->xss_clean($str);

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
		foreach($this->safe_encode as $key => $val)
		{
			if ( ! is_numeric($key) && isset($val['property']))
			{
				if (preg_match("/<".$key.".*?".$val['property']."=(\042|\047)(.*?)\\1.*?>(.*?)<\/".$key.">/is", $str, $matches))
				{
					$property = ee()->security->xss_clean($matches[2]);
					$str = preg_replace(
						"/<".$key.".*?".$val['property']."=(\042|\047).*?\\1.*?>(.*?)<\/".$key.">/is",
						"[".$key."=\\1".$property."\\1]\\2[/".$key."]",
						$str
					);
				}
			}
			elseif (stristr($str, $val.'>') !== FALSE)
			{
				$str = preg_replace("#<".$val.">(.+?)</".$val.">#si", "[$val]\\1[/$val]", $str);
			}
		}

		// Convert anchors to BBCode
		//
		// We do this to prevent allowed HTML from getting converted in the next
		// step Old method would only convert links that had href= as the first
		// tag attribute $str =
		// preg_replace("#<a\s+href=[\"'](\S+?)[\"'](.*?)\>(.*?)</a>#si",
		// "[url=\"\\1\"\\2]\\3[/url]", $str);

		if (stristr($str, '<a') !== FALSE)
		{
			$str = preg_replace("#<a\s+(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);
		}

		// Convert image tags BBCode

		$str = str_replace("/>", ">", $str);

		if (stristr($str, '<img') !== FALSE)
		{
			$str = preg_replace("#<img(.*?)src=\s*[\"'](.+?)[\"'](.*?)\s*\>#si", "[img]\${2}{$this->safe_img_src_end}\\3\\1[/img]", $str);
		}

		if (stristr($str, '://') !== FALSE)
		{
			$str = preg_replace( "#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)\.(jpg|jpeg|gif|png)#i", "\\1[img]http\\4://\\5\\6.\\7[/img]", $str);
		}

		return $this->encode_tags($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Run the Mardown code through a pre processor so we can convert all code
	 * blocks (not inline) to bbcode blocks for highlighting
	 * @param  String $str The string to pre-process
	 * @return String      The pre-processed string
	 */
	protected function markdown_pre_process($str)
	{
		// Must use a named group of codeblock for this to work properly
		$hashes = array();
		$codeblocks = array();
		$extract_callback = function ($matches) use (&$hashes, &$codeblocks) {
			$hash = random_string('md5');
			$hashes[] = $hash;
			$codeblocks[] = "[code]\n".trim($matches['codeblock'])."\n[/code]\n\n";
			return $hash;
		};

		// First, get the fenced code blocks. Fenced code blocks consist of
		// three tildes or backticks in a row on their own line, followed by
		// some code, followed by a matching set of three or more tildes or
		// backticks on their own line again
		if (strpos($str, '```') !== FALSE
			OR strpos($str, '~~~') !== FALSE)
		{
			$str = preg_replace_callback(
				"/
				# We only care about fences that are the beginning of their line
				(^

				# Must start with ~~~ or ``` and only contain that character
				(?:`{3,}|~{3,}))

				# Capture the codeblock AND name it
				(?P<codeblock>.*?)

				# Find the matching bunch of ~ or `
				\\1
				/ixsm",
				$extract_callback,
				$str
			);
		}

		// Second, extract actual code blocks
		if (strpos($str, '[code]') !== FALSE
			&& strpos($str, '[/code]') !== FALSE)
		{
			$str = preg_replace_callback(
				"/\\[code\\](?P<codeblock>.*?)\\[\\/code\\]/s",
				$extract_callback,
				$str
			);
		}

		// Replace tabs with spaces
		if (strpos($str, "\t") !== FALSE)
		{
			$str = preg_replace("/^\t/m", "    ", $str);
		}

		// Now process tab indented code blocks
		if (strpos($str, '    ') !== FALSE)
		{
			$str = preg_replace_callback(
				'/
				# Must be beginning of line OR file
				(?:\n\n|\A\n?)

				# Lines must start with four spaces, using atomic groups here so
				# the regular expression parser can not backtrack. Capture all
				# lines like this in a row.
				((?>[ ]{4}.*\n*)+)

				# Lookahead for non space at the start or end of string
				((?=^[ ]{0,4}\S)|\Z)
				/xm',
				function ($matches) {
					$codeblock = $matches[1];

					// Outdent these code blocks
					$codeblock = preg_replace("/^[ ]{4}(.*)$/m", "$1", $codeblock);

					// Trim the whole string and wrap it in [code]
					return "[code]\n".trim($codeblock)."\n[/code]\n\n";
				},
				$str
			);
		}

		// Put everything back in to place
		return str_replace($hashes, $codeblocks, $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse content to Markdown
	 * @param  string $str     String to parse
	 * @param  array  $options Associative array containing options
	 *                         - smartypants (TRUE/FALSE) enable or disable
	 *                           smartypants
	 *                         - no_markup (TRUE/FALSE) set to TRUE to disable
	 *                           the parsing of markup in Markdown
	 * @return string          Parsed Markdown content
	 */
	public function markdown($str, $options = array())
	{
		// Ignore [code]
		$code_blocks = array();
		preg_match_all('/\<div class="codeblock">(.*?)\<\/div>/uis', $str, $matches);
		foreach ($matches[0] as $match)
		{
			$hash = random_string('md5');
			$code_blocks[$hash] = $match;
			$str = str_replace($match, $hash, $str);
		}

		$parser = new MarkdownExtra;

		// Disable other markup if this is set
		if (isset($options['no_markup'])
			&& get_bool_from_string($options['no_markup']))
		{
			$parser->no_markup = TRUE;
		}

		// Protect any quotes in EE tags from the Markdown and SmartyPants
		// processors.
		$str = $this->protect_quotes_in_tags($str);

		// Parse the Markdown
		$str = $parser->transform($str);

		// Run everything through SmartyPants
		if ( ! isset($options['smartypants'])
			OR get_bool_from_string($options['smartypants']) == TRUE)
		{
			if ( ! class_exists('SmartyPants_Parser')){
				require_once(APPPATH.'libraries/typography/SmartyPants/smartypants.php');
			}
			// 2  ->  "---" for em-dashes; "--" for en-dashes
			$str = SmartyPants($str, 2);
		}

		// Restore the quotes we protected earlier.
		$str = $this->restore_quotes_in_tags($str);

		// Replace <div class="codeblock"> ([code]) blocks.
		foreach ($code_blocks as $hash => $code_block)
		{
			$str = str_replace($hash, $code_block, $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Auto link URLs and email addresses
	 *
	 * @param 	string
	 */
	public function auto_linker($str)
	{
		$str .= ' ';

		// We don't want any links that appear in the control panel
		// (in channel entries, comments, etc.) to point directly at URLs.
		// Why? Becuase the control panel URL will end up in people's referrer
		// logs, this would be a bad thing. So, we'll point all links to the
		// "bounce server"

		if ((REQ == 'CP' && ee()->input->get('M') != 'send_email') OR
			ee()->config->item('redirect_submitted_links') == 'y')
		{
			$this->bounce = ee()->functions->fetch_site_index().QUERY_MARKER.'URL=';
		}

		// Protect URLs that are already in [url] BBCode
		if (strpos($str, '[url') !== FALSE)
		{
			$str = preg_replace("/(\[url[^\]]*?\])http/is", '${1}'.$this->http_hidden, str_replace('[url=http', '[url='.$this->http_hidden, $str));

			$str = preg_replace("/(\[url[^\]]*?\])http/is", '${1}'.$this->http_hidden, str_replace('[url=http', '[url='.$this->http_hidden, $str));
		}

		// New version. Blame Paul if it doesn't work
		// The parentheses on the end attempt to call any content after the URL.
		// This way we can make sure it is not [url=http://site.com]http://site.com[/url]

		// Edit: Added a check for the trailing 6 characters for an edgecase
		// where the inner url was valid, but did not exactly match the other:
		// [url=http://www.iblamepaul.com]www.iblamepaul.com[/url] ;) -pk
		$str = preg_replace_callback("#(^|\s|\(|..\])((http(s?)://)|(www\.))(\w+[^\s\)\<\[]+)(.{0,6})#im", array(&$this, 'auto_linker_callback'), $str);

		// Auto link email
		if (strpos($str, '@') !== FALSE)
		{
			// special treatment if it's in a mailto link
			if (strpos($str, 'mailto:') !== FALSE)
			{
				$email_no_captures = '[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]*';
				$str = preg_replace('/<a\s+[^<>]*?href=(\042|\047)mailto:('.$email_no_captures.')\\1[^<>]*?>([^<]*)<\/a>/i', '[email=\\2]\\3[/email]', $str);
			}

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

	// --------------------------------------------------------------------

	/**
	 * Callback function used above
	 */
	public function auto_linker_callback($matches)
	{
		//  If it is in BBCode, then we do not auto link
		if (strtolower($matches[1]) == 'mg]' OR
			strtolower($matches[1]) == 'rl]' OR
			strtolower($matches[7]) == '[/url]'
			)
		{
			return $matches['0'];
		}

		/** -----------------------------------
		/**  Moved the Comment and Period Modification Here
		/** -----------------------------------*/

		$end = '';

		if (preg_match("/^(.+?)([\.\,\?\!\:\;]+)$/",$matches['6'], $punc_match))
		{
			$end = $punc_match[2];
			$matches[6] = $punc_match[1];
		}

		return	$matches['1'].'[url=http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].']http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].'[/url]'.
				$end.
				$matches['7'];
	}

	// --------------------------------------------------------------------

	/**
	 * Decode BBCode
	 *
	 */
	public function decode_bbcode($str)
	{
		/** -------------------------------------
		/**  Remap some deprecated tags with valid counterparts
		/** -------------------------------------*/

		$str = str_ireplace(array('[strike]', '[/strike]', '[u]', '[/u]'), array('[del]', '[/del]', '[em]', '[/em]'), $str);

		/** -------------------------------------
		/**  Decode BBCode array map
		/** -------------------------------------*/

		foreach($this->safe_decode as $key => $val)
		{
			if (is_array($val)
				&& isset($val['property'])
				&& preg_match_all('/\['.$key.'=(.*?)\](.*?)\[\/'.$key.'\]/is', $str, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $tag_match)
				{
					// Clean up the contents of the property
					$tag_match[1] = htmlspecialchars(
						ee()->security->xss_clean($tag_match[1])
					);

					// If there's any evidence of XSS then don't add anything
					if (stripos($tag_match[0], '[removed]') !== FALSE)
					{
						$str = str_replace($tag_match[0], '', $str);
					}
					else
					{
						$str = str_replace(
							$tag_match[0],
							"<".$val['tag']." ".$val['property']."='".$tag_match[1]."''>".$tag_match[2]."</".$val['tag'].">",
							$str
						);
					}
				}
			}
			else
			{
				$val = (is_array($val)) ? $val['tag'] : $val;
				$str = str_ireplace(
					array('['.$key.']', '[/'.$key.']'),
					array('<'.$val.'>', '</'.$val.'>'),
					$str
				);
			}
		}

		/** -------------------------------------
		/**  Decode codeblock division for code tag
		/** -------------------------------------*/

		$str = $this->_decode_code_tags($str);

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

		if (stripos($str, '[url') !== FALSE)
		{
			$bounce	= ((REQ == 'CP' && ee()->input->get('M') != 'send_email') OR ee()->config->item('redirect_submitted_links') == 'y') ? ee()->functions->fetch_site_index().QUERY_MARKER.'URL=' : '';

			$bad_things	 = array("'",'"', ';', '[', '(', ')', '!', '*', '>', '<', "\t", "\r", "\n", 'document.cookie'); // everything else
			$bad_things2 = array('[', '(', ')', '!', '*', '>', '<', "\t", 'document.cookie'); // style,title attributes
			$exceptions	 = array('http://', 'https://', 'irc://', 'feed://', 'ftp://', 'ftps://', 'mailto:', '/', '#');
			$allowed	 = array('rel', 'title', 'class', 'style', 'target');

			if (preg_match_all("/\[url(.*?)\](.*?)\[\/url\]/is", $str, $matches))
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
						if (($space_pos = strpos($url, ' ')) !== FALSE)
						{
							// If allowed is none- we ditch everything but the url
							if ($this->html_format == 'none')
							{
								$url = substr($url, 0, $space_pos);
							}
							else
							{
								$full_string = $url;
								$url = substr($url, 0, $space_pos);

								$extra = ' '.trim(str_replace($url, '', $full_string));
							}
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

		if (stripos($str, '[img]') !== FALSE)
		{
			$bad_things	 = array("'",'"', ';', '[', '(', ')', '!', '*', '>', '<', "\t", "\r", "\n", 'document.cookie');

			if ($this->allow_img_url == 'y')
			{
				$str = preg_replace_callback("/\[img\](.*?)\[\/img\]/i", array($this, "image_sanitize"), $str);
				// $str = preg_replace("/\[img\](.*?)\[\/img\]/i", "<img src=\\1 />", $str);
			}
			elseif($this->auto_links == 'y' && $this->html_format != 'none')
			{
				if (preg_match_all("/\[img\](.*?)\[\/img\]/is", $str, $matches))
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

		if (stripos($str, '[quote') !== FALSE)
		{
			$str = preg_replace('/\[quote\s+(author=".*?"\s+date=".*?")\]/si', '<blockquote \\1>', $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Replace [div class="codeblock"] with <div class="codeblock">
	 * @param  String $str The string to parse
	 * @return String      The resulsting parsed string
	 */
	private function _decode_code_tags($str)
	{
		if (count($this->code_chunks) > 0)
		{
			foreach ($this->code_chunks as $key => $val)
			{
				$str = str_replace('[div class="codeblock"]{'.$key.'yH45k02wsSdrp}[/div]', '<div class="codeblock">{'.$key.'yH45k02wsSdrp}</div>', $str);
			}
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Make images safe, limited what attributes are carried through
	 *
	 * This also removes parenthesis so that javascript event handlers
	 * can't be invoked.
	 */
	public function image_sanitize($matches)
	{
		if (strpos($matches[1], $this->safe_img_src_end))
		{
			list($url, $extra) = explode($this->safe_img_src_end, $matches[1]);
		}
		else
		{
			$url = $matches[1];
			$extra = '';
		}

		$url = str_replace(array('(', ')'), '', $url);

		$alt	= '';
		$width	= '';
		$height	= '';

		foreach (array('width', 'height', 'alt') as $attr)
		{
			if (preg_match("/\s+{$attr}=(\"|\')([^\\1]*?)\\1/", $extra, $attr_match))
			{
				${$attr} = $attr_match[0];
			}
			elseif ($attr == 'alt')	// always make sure there's some alt text
			{
				$alt = 'alt="" ';
			}
		}

		return "<img src=\"{$url}\" {$alt}{$width}{$height} />";
	}

	// --------------------------------------------------------------------

	/**
	 * Decode and spam protect email addresses
	 */
	public function decode_emails($str)
	{
		if (strpos($str, '[email') === FALSE)
		{
			return $str;
		}

		// [email=your@yoursite]email[/email]

		$str = preg_replace_callback("/\[email=(.*?)\](.*?)\[\/email\]/is", array($this, "create_mailto"),$str);

		// [email]joe@xyz.com[/email]

		$str = preg_replace_callback("/\[email\](.*?)\[\/email\]/is", array($this, "create_mailto"),$str);

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Format Email via callback
	 */
	public function create_mailto($matches)
	{
		if (($space = strpos($matches['1'], ' ')) != FALSE)
		{
			$matches['1'] = substr($matches['1'], 0, $space);
		}

		// get rid of surrounding quotes
		$matches['1'] = preg_replace(array('/(^"|\')/', '/("|\'$)/'), '', $matches['1']);

		$title = ( ! isset($matches['2'])) ? $matches['1'] : $matches['2'];

		if ($this->encode_email == TRUE)
		{
			return $this->encode_email($matches['1'], $title, TRUE);
		}

		return "<a href=\"mailto:".$matches['1']."\">".$title."</a>";
	}

	// --------------------------------------------------------------------

	/**
	 * Font sizing matrix via callback
	 */
	public function font_matrix($matches)
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

	// --------------------------------------------------------------------

	/**
	 * Encode tags
	 */
	public function encode_tags($str)
	{
		return str_replace(array("<", ">"), array("&lt;", "&gt;"), $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Strip IMG tags
	 */
	public function strip_images($str)
	{
		if (strpos($str, '<img') !== FALSE)
		{
			$str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
			$str = preg_replace("#<img\s+.*?src\s*=\s*(.+?)\s*\>#", "\\1", $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Emoticon replacement
	 */
	public function emoticon_replace($str)
	{
		if ($this->smiley_array === FALSE OR $this->parse_smileys === FALSE OR ee()->session->userdata('parse_smileys') == 'n')
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
				$img = "<img src=\"".$this->emoticon_url.$this->smiley_array[$key]['0']."\" width=\"".$this->smiley_array[$key]['1']."\" height=\"".$this->smiley_array[$key]['2']."\" alt=\"".$this->smiley_array[$key]['3']."\" style=\"border:0;\" />";

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

	// --------------------------------------------------------------------

	/**
	 * Word censor
	 */
	public function filter_censored_words($str)
	{
		if ($this->word_censor == FALSE)
		{
			return $str;
		}

		ee()->load->helper('text');
		return word_censor($str, $this->censored_words, $this->censored_replace);
	}

	// --------------------------------------------------------------------

	/**
	 * Colorize code strings
	 */
	public function text_highlight($str)
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

			// Go directly to BB code to avoid extra replace and
			// prevent 'convert to entities' from converting the div tag
			$str = str_replace($matches['0'][$i], '[div class="codeblock"]{'.$this->code_counter.'yH45k02wsSdrp}[/div]', $str);

			$this->code_counter++;
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Convert ampersands to entities
	 */
	function convert_ampersands($str)
	{
		if (strpos($str, '&') === FALSE) return $str;

		$str = preg_replace("/&#(\d+);/", "AMP14TX903DVGHY4QW\\1;", $str);
		$str = preg_replace("/&(\w+);/",  "AMP14TX903DVGHY4QT\\1;", $str);

		return str_replace(array("&","AMP14TX903DVGHY4QW","AMP14TX903DVGHY4QT"),array("&amp;", "&#","&"), $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Encode Email Address
	 */
	public function encode_email($email, $title = '', $anchor = TRUE)
	{
		if (isset(ee()->TMPL) && is_object(ee()->TMPL) &&
			isset(ee()->TMPL->encode_email) &&
			ee()->TMPL->encode_email == FALSE)
		{
			return $email;
		}

		$title = ($title == '') ? $email : $title;

		if (isset($this->encode_type) && $this->encode_type == 'noscript')
		{
			$email = str_replace(array('@', '.'), array(' '.ee()->lang->line('at').' ', ' '.ee()->lang->line('dot').' '), $email);
			return $email;
		}

		$bit = array();

		if ($anchor == TRUE)
		{
			$bit = array(
				'<', 'a ', 'h', 'r', 'e', 'f', '=', '\"', 'm', 'a', 'i', 'l',
				't', 'o', ':'
			);
		}

		for ($i = 0; $i < strlen($email); $i++)
		{
			$bit[] .= " ".ord(substr($email, $i, 1));
		}

		$temp = array();

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
		$span_marker = 'data-eeEncEmail_'.ee()->functions->random('alpha', 10);

		ob_start();

// Regex speed hat tip: http://blog.stevenlevithan.com/archives/faster-trim-javascript
?>

<span <?php echo $span_marker; ?>='1'>.<?php echo lang('encoded_email'); ?></span><script type="text/javascript">
/*<![CDATA[*/
var out = '',
	el = document.getElementsByTagName('span'),
	l = ['<?php echo implode("','", $bit)?>'],
	i = l.length,
	j = el.length;

while (--i >= 0)
	out += unescape(l[i].replace(/^\s\s*/, '&#'));

while (--j >= 0)
	if (el[j].getAttribute('<?php echo $span_marker ?>'))
		el[j].innerHTML = out;
/*]]>*/
</script><?php

		$buffer = ob_get_contents();
		ob_end_clean();

		return str_replace(array("\n", "\t"), '', $buffer);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Emotions Preferences
	 */
	private function _fetch_emotions_prefs()
	{
		if (is_file(PATH_MOD.'emoticon/emoticons.php'))
		{
			require PATH_MOD.'emoticon/emoticons.php';

			if (is_array($smileys))
			{
				$this->smiley_array = $smileys;
				$this->emoticon_url = ee()->config->slash_item('emoticon_url');
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Word Censor Preferences
	 */
	private function _fetch_word_censor_prefs()
	{
		$this->word_censor = TRUE;

		if ($this->word_censor == TRUE && ee()->config->item('censored_words') != '')
		{
			if (ee()->config->item('censor_replacement') !== FALSE)
			{
				$this->censored_replace = ee()->config->item('censor_replacement');
			}

			$words = str_replace('OR', '|', trim(ee()->config->item('censored_words')));

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
	}

	// --------------------------------------------------------------------

	/**
	 * Protect BBCOde
	 *
	 * We don't want BBCode parsed if it's within code examples so we'll
	 * convert the brackets
	 *
	 */
	private function _protect_bbcode($str)
	{
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

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Convert Code Markers back to rendered code.
	 *
	 * The hightlight function called earlier converts the original code strings
	 * into markers so that the auth_xhtml function doesn't attempt to process
	 * the highlighted code chunks.  Here we convert the markers back to their
	 * correct state.
	 *
	 * @param 	string
	 * @param 	string
	 */
	private function _convert_code_markers($str)
	{
		if (count($this->code_chunks) > 0)
		{
			foreach ($this->code_chunks as $key => $val)
			{
				if ($this->text_format == 'legacy_typography')
				{
					// First line takes care of the line break that might be there, which should
					// be a line break because it is just a simple break from the [code] tag.

					// Note: [div class="codeblock"] has been converted to <div class="codeblock"> at this pont
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

		return $str;
	}

	// --------------------------------------------------------------------

}
// END CLASS
/* End of file Typography.php */
/* Location: ./system/expressionengine/libraries/Typography.php */
