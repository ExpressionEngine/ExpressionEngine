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
 * ExpressionEngine Core Typography Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Typography extends CI_Typography {

	var $single_line_pgfs			= TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
	var $text_format				= 'xhtml';  // xhtml, markdown, br, none, or lite
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
	var $emoticon_url				= '';
	var $site_index					= '';
	var $word_censor				= FALSE;
	var $censored_words 			= array();
	var $censored_replace			= '';
	var $text_fmt_types				= array('xhtml', 'markdown', 'br', 'none', 'lite');
	var $text_fmt_plugins			= array();
	var $html_fmt_types				= array('safe', 'all', 'none');
	var $yes_no_syntax				= array('y', 'n');
	var $code_chunks				= array();
	var $code_counter				= 0;
	var $http_hidden 				= NULL; // hash to protect URLs in [url] BBCode
	var $safe_img_src_end			= NULL; // hash to mark end of image URLs during sanitizing of image tags

	// Allowed tags  Note: Specified in initialize()
	var $safe_encode = array();
	var $safe_decode = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->initialize();
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
		$this->single_line_pgfs		= TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
		$this->text_format			= 'xhtml';  // xhtml, markdown, br, none, or lite
		$this->html_format			= 'safe';	// safe, all, none
		$this->auto_links			= 'y';
		$this->allow_img_url		= 'n';
		$this->parse_images			= TRUE;
		$this->allow_headings		= TRUE;
		$this->encode_email			= TRUE;
		$this->encode_type			= 'javascript'; // javascript or noscript
		$this->use_span_tags		= TRUE;
		$this->popup_links			= FALSE;
		$this->bounce				= '';
		$this->smiley_array			= FALSE;
		$this->parse_smileys		= TRUE;
		$this->highlight_code		= TRUE;
		$this->convert_curly		= TRUE;		// Convert Curly Brackets Into Entities
		$this->emoticon_url			= '';
		$this->site_index			= '';
		$this->word_censor			= FALSE;
		$this->censored_words		= array();
		$this->censored_replace		= '';
		$this->text_fmt_types		= array('xhtml', 'markdown', 'br', 'none', 'lite');
		$this->text_fmt_plugins		= array();
		$this->html_fmt_types		= array('safe', 'all', 'none');
		$this->yes_no_syntax		= array('y', 'n');
		$this->code_chunks			= array();
		$this->code_counter			= 0;

		$this->http_hidden			= unique_marker('typography_url_protect'); // hash to protect URLs in [url] BBCode
		$this->safe_img_src_end		= unique_marker('typography_img_src_end'); // hash to mark end of image URLs during sanitizing of image tags

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
			'b'				=> 'b',
			'i'				=> 'i',
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

		foreach (ee()->functions->fetch_file_paths() as $key => $val)
		{
			$str = str_replace(array("{filedir_{$key}}", "&#123;filedir_{$key}&#125;"), $val, $str);
		}

		return $str;
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
		if ($this->parse_images === TRUE)
		{
			$this->file_paths = ee()->functions->fetch_file_paths();
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
			if (ee()->extensions->active_hook('typography_parse_type_start') === TRUE)
			{
				$str = ee()->extensions->call('typography_parse_type_start', $str, $this, $prefs);
			}
		//
		// -------------------------------------------

		/** -------------------------------------
		/**  Set up our preferences
		/** -------------------------------------*/

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
		$separate_parser = ($this->text_format == 'markdown') ? TRUE : FALSE;

		/** -------------------------------------
		/**  Encode PHP tags
		/** -------------------------------------*/

		// Before we do anything else, we'll convert PHP tags into character entities.
		// This is so that PHP submitted in channel entries, comments, etc. won't get parsed.
		// Since you can enable templates to parse PHP, it would open up a security
		// hole to leave PHP submitted in entries and comments intact.
		//
		// If we're dealing with a separate parser, don't encode now in case of
		// code snippets

		ee()->load->helper('security');
		if ( ! $separate_parser)
		{
			$str = encode_php_tags($str);
		}

		/** -------------------------------------
		/**  Encode EE tags
		/** -------------------------------------*/

		// Next, we need to encode EE tags contained in entries, comments, etc. so that they don't get parsed.

		$str = ee()->functions->encode_ee_tags($str, $this->convert_curly);

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
		$str = $this->_protect_bbcode($str);

		//  Strip IMG tags if not allowed
		if ($this->allow_img_url == 'n')
		{
			$str = $this->strip_images($str);
		}

		//  Format HTML
		$str = $this->format_html($str);

		//  Auto-link URLs and email addresses
		if ($this->auto_links == 'y' && ! $separate_parser)
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

		if (REQ == 'CP' && ee()->input->get('M') != 'send_email' && strpos($str, 'href=') !== FALSE)
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
			case 'xhtml':
				$str = $this->auto_typography($str);
				break;
			case 'markdown':
				$str = $this->markdown($str, $prefs);
				break;
			case 'lite':
				$str = $this->format_characters($str); // Used with channel entry titles
				break;
			case 'br':
				$str = $this->nl2br_except_pre($str);
				break;
			default:
				// Plugin of some sort
				if ( ! class_exists('EE_Template'))
				{
					require APPPATH.'libraries/Template.php';
					ee()->TMPL = new EE_Template();
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
				break;
		}

		// Encode PHP post-Markdown parsing
		if ($separate_parser)
		{
			$str = encode_php_tags($str);
		}

		//  Parse emoticons
		$str = $this->emoticon_replace($str);

		//  Parse censored words
		if ($this->word_censor === TRUE && count($this->censored_words > 0))
		{
			ee()->load->helper('text');
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
					$str = str_replace($matches['0'][$j], ee()->functions->encode_email($matches['1'][$j]), $str);
				}
			}
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

		return $str;
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

		foreach($this->safe_encode as $val)
		{
			if (stristr($str, $val.'>') !== FALSE)
			{
				$str = preg_replace("#<".$val.">(.+?)</".$val.">#si", "[$val]\\1[/$val]", $str);
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
	 * Parse content to Markdown
	 * @param  string $str     String to parse
	 * @param  array  $options Associative array containing options
	 *                         - encode_ee_tags (yes/no) can be used to disable
	 *                         	ee tag encoding
	 *                         - smartypants (yes/no) enable or disable
	 *                         	smartypants
	 * @return string          Parsed Markdown content
	 */
	public function markdown($str, $options = array())
	{
		require_once(APPPATH.'libraries/typography/Markdown/markdown.php');

		// Encode EE Tags
		if ( ! isset($options['encode_ee_tags']) OR $options['encode_ee_tags'] == 'yes')
		{
			$str = ee()->functions->encode_ee_tags($str);
		}

		$str = Markdown($str);

		// Run everything through SmartyPants
		if ( ! isset($options['smartypants']) OR $options['smartypants'] == 'yes')
		{
			require_once(APPPATH.'libraries/typography/SmartyPants/smartypants.php');
			$str = SmartyPants($str);
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
			$str = str_ireplace(array('['.$key.']', '[/'.$key.']'),	array('<'.$val.'>', '</'.$val.'>'),	$str);
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
