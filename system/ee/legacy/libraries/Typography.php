<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use Michelf\MarkdownExtra;
use  EllisLab\ExpressionEngine\Core\Autoloader;

/**
 * Core Typography
 */
class EE_Typography {

	// Block level elements that should not be wrapped inside <p> tags
	public $block_elements = 'address|article|aside|audio|blockquote|canvas|div|dl|fieldset|figure|footer|form|h\d|header|hgroup|hr|noscript|object|ol|output|p|pre|script|section|table|ul|video';

	// Elements that should not have <p> and <br /> tags within them.
	public $skip_elements	= 'figure|p|pre|ol|ul|dl|object|table|h\d';

	// Tags we want the parser to completely ignore when splitting the string.
	public $inline_elements = 'a|abbr|acronym|b|bdo|big|br|button|cite|code|del|dfn|em|figcaption|i|img|ins|input|kbd|label|map|mark|q|samp|select|small|span|strong|sub|sup|textarea|tt|var';

	// array of block level elements that require inner content to be within another block level element
	public $inner_block_required = array('blockquote');

	// the last block element parsed
	public $last_block_element = '';

	// whether or not to protect quotes within { curly braces }
	public $protect_braced_quotes = FALSE;

	public $single_line_pgfs = TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
	public $text_format      = 'xhtml';  // xhtml, markdown, br, none, or lite
	public $html_format      = 'safe';	// safe, all, none
	public $auto_links       = 'y';
	public $allow_img_url    = 'n';
	public $bbencode_links   = TRUE;
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
	public $highlight_code   = FALSE;
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

	// tag bracket constants for use in Safe HTML / BBcode parsing
	const HTML_BRACKETS = 1;
	const BBCODE_BRACKETS = 2;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->initialize();
	}

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
		$this->bbencode_links   = TRUE;
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
		$this->highlight_code   = FALSE;
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
			'abbr' => array('properties' => array('title')),
			'b',
			'blockquote',
			'cite',
			'code' => array('properties' => array('class', 'data-language')),
			'del',
			'em',
			'i',
			'ins',
			'mark' => array('properties' => array('class')),
			'pre',
			'span' => array('properties' => array('class')),
			'strong',
			'sub',
			'sup'
		);

		$this->safe_decode = array(
			'abbr'       => array('tag' => 'abbr', 'properties' => array('title')),
			'b'          => 'b',
			'blockquote' => 'blockquote',
			'cite'       => 'cite',
			'code'       => array('tag' => 'code', 'properties' => array('class', 'data-language')),
			'del'        => 'del',
			'em'         => 'em',
			'i'          => 'i',
			'ins'        => 'ins',
			'mark'       => array('tag' => 'mark', 'properties' => array('class')),
			'pre'        => 'pre',
			'quote'      => 'blockquote',
			'span'       => array('tag' => 'span', 'properties' => array('class')),
			'strong'     => 'strong',
			'sub'        => 'sub',
			'sup'        => 'sup'
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

		if (bool_config_item('enable_censoring'))
		{
			$this->word_censor = TRUE;
		}

		/** -------------------------------------
		/**  Fetch plugins
		/** -------------------------------------*/

		ee()->load->model('addons_model');
		$this->text_fmt_plugins = ee()->addons_model->get_plugin_formatting();
	}

	/**
	 * Auto Typography
	 *
	 * This function converts text, making it typographically correct:
	 *	- Converts double spaces into paragraphs.
	 *	- Converts single line breaks into <br /> tags
	 *	- Converts single and double quotes into correctly facing curly quote entities.
	 *	- Converts three dots into ellipsis.
	 *	- Converts double dashes into em-dashes.
	 *  - Converts two spaces into entities
	 *
	 * @access	public
	 * @param	string
	 * @param	bool	whether to reduce more then two consecutive newlines to two
	 * @return	string
	 */
	function auto_typography($str, $reduce_linebreaks = FALSE)
	{
		if ($str == '')
		{
			return '';
		}

		// Standardize Newlines to make matching easier
		if (strpos($str, "\r") !== FALSE)
		{
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		// Reduce line breaks.  If there are more than two consecutive linebreaks
		// we'll compress them down to a maximum of two since there's no benefit to more.
		if ($reduce_linebreaks === TRUE)
		{
			$str = preg_replace("/\n\n+/", "\n\n", $str);
		}

		// HTML comment tags don't conform to patterns of normal tags, so pull them out separately, only if needed
		$html_comments = array();
		if (strpos($str, '<!--') !== FALSE)
		{
			if (preg_match_all("#(<!\-\-.*?\-\->)#s", $str, $matches))
			{
				for ($i = 0, $total = count($matches[0]); $i < $total; $i++)
				{
					$html_comments[] = $matches[0][$i];
					$str = str_replace($matches[0][$i], '{@HC'.$i.'}', $str);
				}
			}
		}

		// match and yank <pre> tags if they exist.  It's cheaper to do this separately since most content will
		// not contain <pre> tags, and it keeps the PCRE patterns below simpler and faster
		if (strpos($str, '<pre') !== FALSE)
		{
			$str = preg_replace_callback("#<pre.*?>.*?</pre>#si", array($this, '_protect_characters'), $str);
		}

		// Convert quotes within tags to temporary markers.
		$str = preg_replace_callback("#<.+?>#si", array($this, '_protect_characters'), $str);

		// Do the same with braces if necessary
		if ($this->protect_braced_quotes === TRUE)
		{
			$str = preg_replace_callback("#\{.+?\}#si", array($this, '_protect_characters'), $str);
		}

		// Convert "ignore" tags to temporary marker.  The parser splits out the string at every tag
		// it encounters.  Certain inline tags, like image tags, links, span tags, etc. will be
		// adversely affected if they are split out so we'll convert the opening bracket < temporarily to: {@TAG}
		$str = preg_replace("#<(/*)(".$this->inline_elements.")([ >])#i", "{@TAG}\\1\\2\\3", $str);

		// Split the string at every tag.  This expression creates an array with this prototype:
		//
		//	[array]
		//	{
		//		[0] = <opening tag>
		//		[1] = Content...
		//		[2] = <closing tag>
		//		Etc...
		//	}
		$chunks = preg_split('/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		$chunks = ($chunks === FALSE) ? (array) $str : $chunks;

		// Build our finalized string.  We cycle through the array, skipping tags, and processing the contained text
		$str = '';
		$process = TRUE;
		$paragraph = FALSE;
		$current_chunk = 0;
		$total_chunks = count($chunks);

		foreach ($chunks as $chunk)
		{
			$current_chunk++;

			// Are we dealing with a tag? If so, we'll skip the processing for this cycle.
			// Well also set the "process" flag which allows us to skip <pre> tags and a few other things.
			if (preg_match("#<(/*)(".$this->block_elements.").*?>#", $chunk, $match))
			{
				if (preg_match("#".$this->skip_elements."#", $match[2]))
				{
					$process =  ($match[1] == '/') ? TRUE : FALSE;
				}

				if ($match[1] == '')
				{
					$this->last_block_element = $match[2];
				}

				$str .= $chunk;
				continue;
			}

			if ($process == FALSE)
			{
				$str .= $chunk;
				continue;
			}

			//  Force a newline to make sure end tags get processed by _format_newlines()
			if ($current_chunk == $total_chunks)
			{
				$chunk .= "\n";
			}

			//  Convert Newlines into <p> and <br /> tags
			$str .= $this->_format_newlines($chunk);
		}

		// No opening block level tag?  Add it if needed.
		if ( ! preg_match("/^\s*<(?:".$this->block_elements.")/i", $str))
		{
			$str = preg_replace("/^(.*?)<(".$this->block_elements.")/i", '<p>$1</p><$2', $str);
		}

		// Convert quotes, elipsis, em-dashes, non-breaking spaces, and ampersands
		$str = $this->format_characters($str);

		// restore HTML comments
		for ($i = 0, $total = count($html_comments); $i < $total; $i++)
		{
			// remove surrounding paragraph tags, but only if there's an opening paragraph tag
			// otherwise HTML comments at the ends of paragraphs will have the closing tag removed
			// if '<p>{@HC1}' then replace <p>{@HC1}</p> with the comment, else replace only {@HC1} with the comment
			$str = preg_replace('#(?(?=<p>\{@HC'.$i.'\})<p>\{@HC'.$i.'\}(\s*</p>)|\{@HC'.$i.'\})#s', $html_comments[$i], $str);
		}

		// Final clean up
		$table = array(

						// If the user submitted their own paragraph tags within the text
						// we will retain them instead of using our tags.
						'/(<p[^>*?]>)<p>/'	=> '$1',

						// Reduce multiple instances of opening/closing paragraph tags to a single one
						'#(</p>)+#'			=> '</p>',
						'/(<p>\W*<p>)+/'	=> '<p>',

						// Clean up stray paragraph tags that appear before block level elements
						'#<p></p><('.$this->block_elements.')#'	=> '<$1',

						// Clean up stray non-breaking spaces preceeding block elements
						'#(&nbsp;\s*)+<('.$this->block_elements.')#'	=> '  <$2',

						// Replace the temporary markers we added earlier
						'/\{@TAG\}/'		=> '<',
						'/\{@DQ\}/'			=> '"',
						'/\{@SQ\}/'			=> "'",
						'/\{@DD\}/'			=> '--',
						'/\{@NBS\}/'		=> '  ',

						// An unintended consequence of the _format_newlines function is that
						// some of the newlines get truncated, resulting in <p> tags
						// starting immediately after <block> tags on the same line.
						// This forces a newline after such occurrences, which looks much nicer.
						"/><p>\n/"			=> ">\n<p>",

						// Similarly, there might be cases where a closing </block> will follow
						// a closing </p> tag, so we'll correct it by adding a newline in between
						"#</p></#"			=> "</p>\n</"
						);

		// Do we need to reduce empty lines?
		if ($reduce_linebreaks === TRUE)
		{
			$table['#<p>\n*</p>#'] = '';
		}
		else
		{
			// If we have empty paragraph tags we add a non-breaking space
			// otherwise most browsers won't treat them as true paragraphs
			$table['#<p></p>#'] = '<p>&nbsp;</p>';
		}

		return preg_replace(array_keys($table), $table, $str);

	}

	/**
	 * Format Characters
	 *
	 * This function mainly converts double and single quotes
	 * to curly entities, but it also converts em-dashes,
	 * double spaces, and ampersands
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function format_characters($str)
	{
		static $table;

		if ( ! isset($table))
		{
			$table = array(
							// nested smart quotes, opening and closing
							// note that rules for grammar (English) allow only for two levels deep
							// and that single quotes are _supposed_ to always be on the outside
							// but we'll accommodate both
							// Note that in all cases, whitespace is the primary determining factor
							// on which direction to curl, with non-word characters like punctuation
							// being a secondary factor only after whitespace is addressed.
							'/\'"(\s|$)/'					=> '&#8217;&#8221;$1',
							'/(^|\s|<p>)\'"/'				=> '$1&#8216;&#8220;',
							'/\'"(\W)/'						=> '&#8217;&#8221;$1',
							'/(\W)\'"/'						=> '$1&#8216;&#8220;',
							'/"\'(\s|$)/'					=> '&#8221;&#8217;$1',
							'/(^|\s|<p>)"\'/'				=> '$1&#8220;&#8216;',
							'/"\'(\W)/'						=> '&#8221;&#8217;$1',
							'/(\W)"\'/'						=> '$1&#8220;&#8216;',

							// single quote smart quotes
							'/\'(\s|$)/'					=> '&#8217;$1',
							'/(^|\s|<p>)\'/'				=> '$1&#8216;',
							'/\'(\W)/'						=> '&#8217;$1',
							'/(\W)\'/'						=> '$1&#8216;',

							// double quote smart quotes
							'/"(\s|$)/'						=> '&#8221;$1',
							'/(^|\s|<p>)"/'					=> '$1&#8220;',
							'/"(\W)/'						=> '&#8221;$1',
							'/(\W)"/'						=> '$1&#8220;',

							// apostrophes
							"/(\w)'(\w)/"					=> '$1&#8217;$2',

							// Em dash and ellipses dots
							'/\s?\-\-\s?/'					=> '&#8212;',
							'/(\w)\.{3}/'					=> '$1&#8230;',

							// double space after sentences
							'/(\W)  /'						=> '$1&nbsp; ',

							// ampersands, if not a character entity
							'/&(?!#?[a-zA-Z0-9]{2,};)/'		=> '&amp;'
						);
		}

		return preg_replace(array_keys($table), $table, $str);
	}

	/**
	 * Format Newlines
	 *
	 * Converts newline characters into either <p> tags or <br />
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function _format_newlines($str)
	{
		if ($str == '')
		{
			return $str;
		}

		if (strpos($str, "\n") === FALSE  && ! in_array($this->last_block_element, $this->inner_block_required))
		{
			return $str;
		}

		// Convert two consecutive newlines to paragraphs
		$str = str_replace("\n\n", "</p>\n\n<p>", $str);

		// Convert single spaces to <br /> tags
		$str = preg_replace("/([^\n])(\n)(?=[^\n])/", "\\1<br />\\2\\3", $str);

		// Wrap the whole enchilada in enclosing paragraphs
		if ($str != "\n")
		{
			// We trim off the right-side new line so that the closing </p> tag
			// will be positioned immediately following the string, matching
			// the behavior of the opening <p> tag
			$str =  '<p>'.rtrim($str).'</p>';
		}

		// Remove empty paragraphs if they are on the first line, as this
		// is a potential unintended consequence of the previous code
		$str = preg_replace("/<p><\/p>(.*)/", "\\1", $str, 1);

		return $str;
	}

	/**
	 * Protect Characters
	 *
	 * Protects special characters from being formatted later
	 * We don't want quotes converted within tags so we'll temporarily convert them to {@DQ} and {@SQ}
	 * and we don't want double dashes converted to emdash entities, so they are marked with {@DD}
	 * likewise double spaces are converted to {@NBS} to prevent entity conversion
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	function _protect_characters($match)
	{
		return str_replace(array("'",'"','--','  '), array('{@SQ}', '{@DQ}', '{@DD}', '{@NBS}'), $match[0]);
	}

	/**
	 * Convert newlines to HTML line breaks except within PRE tags
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function nl2br_except_pre($str)
	{
		$ex = explode("pre>",$str);
		$ct = count($ex);

		$newstr = "";
		for ($i = 0; $i < $ct; $i++)
		{
			if (($i % 2) == 0)
			{
				$newstr .= nl2br($ex[$i]);
			}
			else
			{
				$newstr .= $ex[$i];
			}

			if ($ct - 1 != $i)
				$newstr .= "pre>";
		}

		return $newstr;
	}

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
		$str = str_replace(array('>:-(', '>:('), array(':rage:', ':angry:'), $str);

		//  Highlight text within [code] tags
		// If highlighting is enabled, we'll highlight <pre> tags as well.
		if ($this->highlight_code == TRUE)
		{
			$str = str_replace(array('[pre]', '[/pre]'), array('[code]', '[/code]'), $str);
		}

		// We don't want BBCode parsed if it's within code examples so we'll
		// convert the brackets
		$str = $this->_protect_bbcode($str);

		// Parse [code] blocks
		$str = $this->_parse_code_blocks($str);

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
		if (REQ == 'CP' && $this->bbencode_links && strpos($str, 'href=') !== FALSE)
		{
			$str = preg_replace("#<a\s+(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);
		}

		//  Decode BBCode
		if ($this->text_format != 'none')
		{
			$str = $this->decode_bbcode($str);
		}

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

		// Parser-specific post_process
		if ($this->separate_parser
			&& method_exists($this, $this->text_format.'_post_process'))
		{
			$str = $this->{$this->text_format.'_post_process'}($str);
		}

		// Clean code tags
		$str = $this->_clean_code_blocks($str);

		//  Parse emoticons
		$str = $this->emoticon_replace($str);

		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- disable_emoji_shorthand => prevent turning text like :rocket: into 🚀 (y/n, default n)
		/* -------------------------------------------*/

		if (bool_config_item('disable_emoji_shorthand') === FALSE)
		{
			$str = ee('Format')->make('Text', $str)->emojiShorthand();
		}

		//  Parse censored words
		$str = $this->filter_censored_words($str);

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
							(file_exists(PATH_ADDONS.'pi.'.$prefs['text_format'].'.php') OR
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
		$this->separate_parser = ($this->text_format == 'markdown');
		$this->auto_links      = ($this->separate_parser) ? 'n' : $this->auto_links;
	}

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
				require_once PATH_ADDONS.'pi.'.$this->text_format.'.php';
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
			$this->quote_marker = md5(time() . 'quote_marker');
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

		$str = ee('Security/XSS')->clean($str);

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
			if ( ! is_numeric($key) && isset($val['properties']))
			{
				// grab tag blocks, plus we need to match the full open tag
				$matches = $this->matchFullTags($key, $str, self::HTML_BRACKETS);

				foreach ($matches as $match)
				{
					$str = str_replace(
						$match[0],
						$this->buildTag($key, $val['properties'], $match[1], $match[2], self::BBCODE_BRACKETS),
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

	/**
	 * Build Tag
	 *
	 * @param string $name the tag name
	 * @param array $allowed_attributes array of tag attributes to allow
	 * @param string $attribute_str a string of tag attributes, e.g. foo="bar" bat="bag"
	 * @param string $tagdata the tag's inner contents
	 * @param int $bracket_style constant-based param, one of BBCODE_BRACKETS or HTML_BRACKETS, to use in the tags
	 * @return string
	 **/
	private function buildTag($name, $allowed_attributes = array(), $attribute_str = '', $tagdata = '', $bracket_style = self::HTML_BRACKETS)
	{
		$tag_params = '';

		// inside the opening tag block $tag_match[1], grab all parameters
		$param_matches = $this->matchTagAttributes($attribute_str);

		foreach ($param_matches as $p_match)
		{
			// only keep the ones we allow, ditch the rest
			if (in_array($p_match[1], $allowed_attributes))
			{
				$attr_content = htmlspecialchars(
					ee('Security/XSS')->clean($p_match[3]),
					ENT_QUOTES,
					'UTF-8'
				);

				$tag_params .= ' '.$p_match[1].'='.$p_match[2].$attr_content.$p_match[2];
			}
		}

		list($ob, $cb) = $this->getBracketsByStyle($bracket_style);
		return $ob.$name.$tag_params.$cb.$tagdata.$ob.'/'.$name.$cb;
	}

	/**
	 * Get Brackets By Style
	 *
	 * @param int $bracket_style constant-based param, one of BBCODE_BRACKETS or HTML_BRACKETS
	 * @param bool $preg_quote Whether to return a preg_quoted version of the bracket
	 * @return array [opening bracket, closing bracket]
	 **/
	private function getBracketsByStyle($bracket_style = self::HTML_BRACKETS, $preg_quote = FALSE)
	{
		switch ($bracket_style)
		{
			case self::BBCODE_BRACKETS:
				$ob = ($preg_quote) ? '\[' : '[';
				$cb = ($preg_quote) ? '\]' : ']';
				break;
			case self::HTML_BRACKETS:
			default:
				$ob = '<';
				$cb = '>';
		}

		return array($ob, $cb);
	}

	/**
	 * Match Full Tags
	 *
	 * @param string $name the tag name
	 * @param string $string the text to match against
	 * @param int $bracket_style constant-based param, one of BBCODE_BRACKETS or HTML_BRACKETS
	 * @return array preg_match_all() array of all <name> tags in $string
	 **/
	private function matchFullTags($name, $string, $bracket_style = self::HTML_BRACKETS)
	{
		list($ob, $cb) = $this->getBracketsByStyle($bracket_style, TRUE);
		preg_match_all("/(${ob}${name}.*?${cb})(.*?)${ob}\/${name}${cb}/is", $string, $matches, PREG_SET_ORDER);

		return $matches;
	}

	/**
	 * Match Tag Attributes
	 *
	 * @param string $open_tag the full open tag, e.g. <name foo="bar" bat="bag">
	 * @return array preg_match_all() array of all tag parameters in $open_tag
	 **/
	private function matchTagAttributes($open_tag)
	{
		preg_match_all("/(\S+?)\s*=\s*(\"|\')([^\\2]*?)\\2/is", $open_tag, $attr_matches, PREG_SET_ORDER);
		return $attr_matches;
	}

	/**
	 * Clean bbcode from Markdown style code blocks
	 *
	 * @param  String $str The string to pre-process
	 * @return String      The pre-processed string
	 */
	protected function markdown_pre_process($str)
	{
		$protect_bbcode = function($matches) {
			$code = str_replace(
				array('[', ']'),
				array('&#91;', '&#93;'),
				$matches[2]
			);

			// no choice, if Safe HTML format, we need to parse code blocks
			// with our bbcode, or any brackets in Markdown code blocks will be
			// double encoded, e.g. -&amp;gt;
			if ($this->html_format == 'safe')
			{
				return '[code]'.$code.'[/code]';
			}

			return $matches[1].$code.$matches[1];
		};

		// make sure no one is sneaking things into links. XSS Clean won't pick these up since they aren't real markup
		$str = $this->unencodeMarkdownLinks($str);
		$str = $this->unencodeMarkdownReferenceLinks($str);

		// Codefences
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
				(.*?)

				# Find the matching bunch of ~ or `
				\\1
				/ixsm",
				$protect_bbcode,
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
					return str_replace(
						array('[', ']'),
						array('&#91;', '&#93;'),
						$matches[0]
					);
				},
				$str
			);
		}

		// Put everything back in to place
		return $str;
	}

	private function getMarkdownLinks($str, $options = 0)
	{
		// these are protected class properties of Markdown
		// copied here to keep the regex below sane
		$nested_brackets_depth = 6;
		$nested_brackets_re =
			str_repeat('(?>[^\[\]]+|\[', $nested_brackets_depth).
			str_repeat('\])*', $nested_brackets_depth);

		$nested_url_parenthesis_depth = 4;
		$nested_url_parenthesis_re =
			str_repeat('(?>[^()\s]+|\(', $nested_url_parenthesis_depth).
			str_repeat('(?>\)))*', $nested_url_parenthesis_depth);

		// regex from in-line style links in Markdown::doAnchors()
		if (preg_match_all('{
			(				# wrap whole match in $1
			  \[
				('.$nested_brackets_re.')	# link text = $2
			  \]
			  \(			# literal paren
				[ \n]*
				(?:
					<(.+?)>	# href = $3
				|
					('.$nested_url_parenthesis_re.')	# href = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# Title = $7
				  \6		# matching quote
				  [ \n]*	# ignore any spaces/tabs between closing quote and )
				)?			# title is optional
			  \)
			)
			}xs',
			$str,
			$link_matches,
			$options)
			)
		{
			return $link_matches;
		}

		return NULL;
	}

	/**
	 * Unencode Markdown Links
	 *
	 * Turns:
	 * 		[Link](&#x68;&#x74;&#x74;&#x70;&#x73;&#x3A;&#x2F;&#x2F;&#x65;&#x78;&#x61;&#x6D;&#x70;&#x6C;&#x65;&#x2E;&#x63;&#x6F;&#x6D;&#x2F;)
	 * Into:
	 * 		[Link](https://example.com/)
	 *
	 * @param string $str the text to be processed
	 * @return string String with entities decoded in Markdown reference links
	 **/
	private function unencodeMarkdownLinks($str)
	{
		if ( ! $link_matches = $this->getMarkdownLinks($str))
		{
			return $str;
		}

		$count = count($link_matches[0]);

		// decode entities in captures we will use for replacement
		for ($i = 2; $i <= 7; $i++)
		{
			for ($j = 0; $j < $count; $j++)
			{
				$link_matches[$i][$j] = ee('Security/XSS')->entity_decode($link_matches[$i][$j]);
			}
		}

		// replace original full match with the decoded version
		foreach ($link_matches[0] as $key => $match)
		{
			$space = $link_matches[7][$key] ? ' ' : '';
			$new = '['.
						$link_matches[2][$key]. // link text
					']('.
						$link_matches[3][$key].$link_matches[4][$key]. // one of these will be the href
						$space. // Space between URL and title= attribute, if needed
						$link_matches[6][$key]. // " or '
						$link_matches[7][$key]. // optional title= attribute
						$link_matches[6][$key]. // " or '
					')';

			$str = str_replace($match, $new, $str);
		}

		return $str;
	}

	/**
	 * Unencode Markdown Reference Links
	 *
	 * Turns:
	 * 		[1]: &#x68;&#x74;&#x74;&#x70;&#x73;&#x3A;&#x2F;&#x2F;&#x65;&#x78;&#x61;&#x6D;&#x70;&#x6C;&#x65;&#x2E;&#x63;&#x6F;&#x6D;&#x2F;
	 * Into:
	 * 		[1]: https://example.com/
	 *
	 * @param string $str the text to be processed
	 * @return string String with entities decoded in Markdown reference links
	 **/
	private function unencodeMarkdownReferenceLinks($str)
	{
		// set to 1 less than Markdown's $tab_width property
		$less_than_tab = 3;

		// regex from Markdown::stripLinkDefinitions()
		if ( ! $count = preg_match_all(
			'{
			^[ ]{0,'.$less_than_tab.'}\[(.+)\][ ]?:	# id = $1
			  [ ]*
			  \n?				# maybe *one* newline
			  [ ]*
			(?:
			  <(.+?)>			# url = $2
			|
			  (\S+?)			# url = $3
			)
			  [ ]*
			  \n?				# maybe one newline
			  [ ]*
			(?:
				(?<=\s)			# lookbehind for whitespace
				["(]
				(.*?)			# title = $4
				[")]
				[ ]*
			)?	# title is optional
			(?:\n+|\Z)
			}xm',
			$str,
			$link_matches)
			)
		{
			return $str;
		}

		// decode entities in captures we will use for replacement
		for ($i = 2; $i <= 4; $i++)
		{
			for ($j = 0; $j < $count; $j++)
			{
				$link_matches[$i][$j] = ee('Security/XSS')->entity_decode($link_matches[$i][$j]);
			}
		}

		// replace original full match with the decoded version
		foreach ($link_matches[0] as $key => $match)
		{
			$title = '';

			if (empty($link_matches[4][$key]))
			{
				$title = '';
			}
			else
			{
				if (strpos($link_matches[4][$key], '"') !== FALSE)
				{
					$title = ' ('.$link_matches[4][$key].')';
				}
				else
				{
					$title = ' "'.$link_matches[4][$key].'"';
				}
			}

			$newline = (substr($match, -1) === "\n") ? "\n" : '';

			$new = '['.
					$link_matches[1][$key]. // link id
					']: '.
					$link_matches[2][$key].$link_matches[3][$key]. // one of these will be the href
					$title. // empty or optional title
					$newline; // preserve newlines

			$str = str_replace($match, $new, $str);
		}

		return $str;
	}

	/**
	 * Formats an entry title for front-end presentation; things like converting
	 * EE tag brackets, filtering for safe HTML, and converting characters to
	 * their fancy alternatives
	 *
	 * @param String	Entry title
	 * @return String	Formatted entry title
	 */
	public function formatTitle($title)
	{
		$title = str_replace(array('{', '}'), array('&#123;', '&#125;'), $title);

		// Convert any unterminated `&` to `&amp;` in the title so that titles
		// like "M&Ms" don't end up as "M&Ms;"
		if (strpos($title, '&') !== FALSE &&
			preg_match_all('#&([a-z]{2,})([\x00-\x20])*;?#i', $title, $matches))
		{
			foreach ($matches[0] as $i => $match)
			{
				if (strpos($match, ';') === FALSE)
				{
					$title = str_replace($match, '&amp;' . $matches[1][$i] . $matches[2][$i], $title);
				}
			}
		}

		// Strip unsafe HTML and attributes from title
		// Preserve old HTML format, because yay singletons
		$existing_format = $this->html_format;
		$this->html_format = 'safe';
		$title = $this->format_html($title);

		// format_html() turns safe HTML into BBCode
		$title = $this->decode_bbcode($title);

		// Put back old format
		$this->html_format = $existing_format;

		// hit emoji shortands
		$title = ee('Format')->make('Text', $title)->emojiShorthand();

		// and finally some basic curly quotes, em dashes, etc.
		$title = $this->format_characters($title);

		return $title;
	}

	/**
	 * Markdown Post Process
	 *
	 * The markdown library tries to be clever and encodes & to &amp;, but we've
	 * sent it some &#91; to prevent bbcode from running. We need to undo _some_
	 * of Markdown's encoding
	 *
	 * @param  string $str The string post Markdown processing
	 * @return string The string after post processing
	 */
	protected function markdown_post_process($str)
	{
		return str_replace(
			array('&amp;#91;', '&amp;#93;'),
			array('&#91;', '&#93;'),
			$str
		);
	}

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
		if ($link_matches = $this->getMarkdownLinks($str, PREG_SET_ORDER))
		{
			foreach ($link_matches as $match)
			{
				// It felt too heavy handed to do a global replace of all URLs
				// that matched, so (for now) we'll only replace the URLs that
				// the REGEX matched. (that's why the '[]' and '(' are being
				// concatenated)
				$str = str_replace('['.$match[2].']', '['.$this->decodeIDN($match[2]).']', $str);
				$str = str_replace('('.$match[4], '('.$this->decodeIDN($match[4]), $str);
				$str = str_replace($match[4], str_replace(' ', '%20', $match[4]), $str);
			}
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

		return $str;
	}

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

		$url = 'http'.
			   $matches['4'].'://'.
			   $matches['5'].
			   $matches['6'];

	   $url = $this->decodeIDN($url);

	   return $matches['1'].'[url='.$url.']'.$url.'[/url]'.$end.$matches['7'];
	}

	/**
	 * Decode BBCode
	 *
	 */
	public function decode_bbcode($str)
	{

		// Remap some deprecated tags with valid counterparts
		$str = str_ireplace(array('[strike]', '[/strike]', '[u]', '[/u]'), array('[del]', '[/del]', '[em]', '[/em]'), $str);

		// Abbr shorthand, the special snowflake:
		// [abbr="some title"]ST[/abbr]
		// we will let the standard properties whitelist below sanitize
		if (strpos($str, '[abbr=') !== FALSE)
		{
			$str = preg_replace("/\[abbr=(\"|\')(.*?)\\1\](.*?)\[\/abbr\]/si", "[abbr title=\"\\2\"]\\3[/abbr]", $str);
		}

		// Decode BBCode array map
		foreach($this->safe_decode as $key => $val)
		{

			if (is_array($val)
				&& isset($val['properties'])
				&& $matches = $this->matchFullTags($key, $str, self::BBCODE_BRACKETS))
			{
				foreach ($matches as $tag_match)
				{
					// If there's any evidence of XSS then don't add anything
					if (stripos($tag_match[0], '[removed]') !== FALSE)
					{
						$str = str_replace($tag_match[0], '', $str);
					}
					else
					{
						$str = str_replace(
							$tag_match[0],
							$this->buildTag($val['tag'], $val['properties'], $tag_match[1], $tag_match[2], self::HTML_BRACKETS),
							$str
						);
					}
				}
			}

			// Does this tag pair exist without attributes? Replace it
			if (preg_match_all('/\['.$key.']?(.*?)[\'"]?\]/is', $str, $matches, PREG_SET_ORDER))
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

					$has_target = strpos($matches['0'][$i], 'target=') !== FALSE;

					// Ensure new windows don't have access to window.opener
					if ($has_target)
					{
						$extra = $this->addAttribute('rel', 'noopener', $extra);
					}

					if ( ! $has_target && $this->popup_links)
					{
						$extra = $this->addAttribute('target', '_blank', $extra);
						$extra = $this->addAttribute('rel', 'noopener', $extra);
					}

					if ($bounce != '')
					{
						$url = urlencode($url);
					}

					$str = str_replace($matches['0'][$i], '<a href="'.$bounce.trim($url).'"'.$extra.'>'.$this->decodeIDN($matches['2'][$i])."</a>", $str);
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
		/**  Decode color tags
		/** -------------------------------------*/

		if (strpos($str, '[color=') !== FALSE)
		{
			$str = preg_replace_callback(
				"/\[color=(.*?)\](.*?)\[\/color\]/si",
				array($this, 'cleanBBCodeAttributesColor'),
				$str
			);
		}

		/** -------------------------------------
		/**  Decode size tags
		/** -------------------------------------*/

		if (strpos($str, '[size=') !== FALSE)
		{
			$str = preg_replace_callback(
				"/\[size=(.*?)\](.*?)\[\/size\]/si",
				array($this, 'cleanBBCodeAttributesSize'),
				$str
			);
		}

		/** -------------------------------------
		/**  Style tags
		/** -------------------------------------*/

		// [style=class_name]stuff..[/style]

		if (strpos($str, '[style=') !== FALSE)
		{
			$str = preg_replace_callback(
				"/\[style=(.*?)\](.*?)\[\/style\]/si",
				array($this, 'cleanBBCodeAttributesStyle'),
				$str
			);
		}

		/** -------------------------------------
		/**  Attributed quotes, used in the Forum module
		/** -------------------------------------*/

		// [quote author="Brett" date="11231189803874"]...[/quote]

		if (stripos($str, '[quote ') !== FALSE)
		{
			$str = preg_replace_callback(
				'/\[quote\s+author="(.*?)"\s+date="(.*?)"]/si',
				array($this, 'cleanBBCodeAttributesQuote'),
				$str
			);
		}

		return $str;
	}

	/**
	 * Adds HTML attribute to a string, or adds the given value to the attribute
	 * if it already exists in the string
	 *
	 * @param array $name Attribute name, such as 'target'
	 * @param array $value Attribute value, such as '_blank'
	 * @param array $str Attributes string, such as 'href="hi" rel="external"'
	 * @return string Passed attributes string with new attribute and/or value
	 **/
	private function addAttribute($name, $value, $str)
	{
		$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

		if (strpos($str, $name.'=') === FALSE)
		{
			$str .= ' '.$name.'="'.$value.'"';
		}
		else
		{
			$str = preg_replace('/'.preg_quote($name, '/').'=(\042|\047)?/', '$0'.$value.' ', $str);
		}

		return $str;
	}

	/**
	 * Clean BBCode Attributes from [quote]
	 *
	 * @param array $matches preg_match of the valid opening [quote author="foo" date="12345678"]
	 * @return string HTML blockquote open tag
	 **/
	private function cleanBBCodeAttributesQuote($matches)
	{
		$author = htmlentities($matches[1], ENT_QUOTES, 'UTF-8');
		$date = filter_var($matches[2], FILTER_SANITIZE_NUMBER_INT);
		return "<blockquote author=\"${author}\" date=\"${date}\">";
	}

	/**
	 * Clean BBCode Attributes from [size]
	 *
	 * @param array $matches preg_match of a valid [size=3]text[/size]
	 * @return string HTML span tag with a font-size applied
	 **/
	private function cleanBBCodeAttributesSize($matches)
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

		return '<span style="font-size:'.$size.';">'.$matches['2'].'</span>';
	}

	/**
	 * Clean BBCode Attributes from [color]
	 *
	 * @param array $matches preg_match of a valid [color=red]text[/color]
	 * @return string HTML span tag with color applied
	 **/
	private function cleanBBCodeAttributesColor($matches)
	{
		return '<span style="color:'.
			preg_replace('/[^a-z]/is', '', $matches[1]).
			';">'.
			$matches[2].
			'</span>';
	}

	/**
	 * Clean BBCode Attributes from [style=some_class]
	 *
	 * @param array $matches preg_match of valid [style=some_class]text[/style]
	 * @return string HTML span tag with a class attributed applied
	 **/
	private function cleanBBCodeAttributesStyle($matches)
	{
		return '<span class="'.
			preg_replace('/[^ \w\-]/is', '', $matches[1]).
			'">'.
			$matches[2].
			'</span>';
	}

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

	/**
	 * Font sizing matrix via callback
	 */
	public function font_matrix($matches)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('3.4.0');

		return $this->cleanBBCodeAttributesSize($matches);
	}

	/**
	 * Encode tags
	 */
	public function encode_tags($str)
	{
		return str_replace(array("<", ">"), array("&lt;", "&gt;"), $str);
	}

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

	/**
	 * Emoticon replacement
	 *
	 * v4 change: just remap text smileys to emoji, forget the rest, gross
	 */
	public function emoticon_replace($str)
	{
		if (ee()->config->item('enable_emoticons') == 'n' OR $this->parse_smileys === FALSE OR ee()->session->userdata('parse_smileys') == 'n')
		{
			return $str;
		}

		// remap text faces to emoji, is mo betta
		// somewhat arbitrary, based on what some apps auto-convert for you
		$emoji_remap = [
			':-)'     => ':blush:',
			':)'      => ':blush:',
			';-)'     => ':wink:',
			';)'      => ':wink:',
			':-S'     => ':confounded:',
			':-P'     => ':stuck_out_tongue:',
			'%-P'     => ':stuck_out_tongue_closed_eyes:',
			';-P'     => ':stuck_out_tongue_winking_eye:',
			':P'      => ':stuck_out_tongue:',
			'8-/'     => ':face_with_rolling_eyes:',
			':-/'     => ':confused:',
			':mad:'   => ':angry:',
		];

		foreach ($emoji_remap as $smiley => $short_name)
		{
			foreach(array(' ', "\t", "\n", "\r", '.', ',', '>') as $char)
			{
				$str = str_replace($char.$smiley, $char.$short_name, $str);
			}
		}

		return $str;
	}

	/**
	 * Word censor
	 */
	public function filter_censored_words($str)
	{
		if ($this->word_censor == FALSE)
		{
			return $str;
		}

		return (string) ee('Format')->make('Text', $str)->censor();
	}

	/**
	 * Colorize code strings
	 */
	public function text_highlight($str)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('3.3.0');

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

<span <?php echo $span_marker; ?>='1'>.<?php echo lang('encoded_email'); ?></span><script>
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

	/**
	 * Protect BBCode within code blocks
	 * @param  string $str The string to protect
	 * @return string The protected string
	 */
	private function _protect_bbcode($str)
	{
		if (preg_match_all("/(\[code.*?\])(.*?)\[\/code\]/si", $str, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$temp = str_replace(
					array('[', ']'),
					array('&#91;', '&#93;'),
					$match[2]
				);
				$temp = trim($temp);

				$str = str_replace($match[0], $match[1].$temp.'[/code]', $str);
			}
		}

		return $str;
	}

	/**
	 * Parse [code] blocks
	 *
	 * @param string $str the String to parse [code] blocks in
	 * @return string The string now with parsed [code] blocks
	 */
	private function _parse_code_blocks($str)
	{
		if (strpos($str, '[code') !== FALSE)
		{
			if ($this->highlight_code == TRUE)
			{
				$str = $this->text_highlight($str);
			}
			else
			{
				// known, unsupported edge case:
				// [code class="foo"]...[/code]
				$str = str_replace(
					array('[code]', '[/code]'),
					array('<pre><code>', '</code></pre>'),
					$str
				);

				// Handle `[code]` tags with a property
				$str = preg_replace(
					"/\[code=(['\"])?(.*?)\\1?]/s",
					"<pre><code class=\"$2 language-$2\" data-language=\"$2\">",
					$str
				);
			}
		}

		return $str;
	}

	/**
	 * Clean up code blocks after everything has been rendered
	 *
	 * @param  string $str The string to check for code blocks
	 * @return string The cleaned up output
	 */
	private function _clean_code_blocks($str)
	{
		if (strpos($str, '<code') === FALSE)
		{
			return $str;
		}

		static $pre;
		static $post;

		if ( ! isset($pre))
		{
			$pre  = (string) ee()->config->item('code_block_pre');
			$post = (string) ee()->config->item('code_block_post');
		}

		return preg_replace_callback(
			"/(<pre>)?(<code.*?>)(.*?)(<\/code>)(<\/pre>)?/is",
			function ($matches) use ($pre, $post) {
				$code = ee('Format')->make('Text', $matches[3])->encodeEETags(['encode_vars' => TRUE]);

				// deal with possible double-encoded HTML brackets from some parsers
				// these are re-encoded singly below
				$code = str_replace(['&amp;lt;', '&amp;gt;'], ['<', '>'], $code);

				// full code block or inline <code>?
				if ($matches[1])
				{
					return  $pre .                      // code_block_pre config override
							$matches[1] .               // <pre>
							$matches[2] .               // <code class="foo">
							$this->encode_tags($code) . // the code sample
							$matches[4] .               // </code>
							$matches[5] .               // </pre>
							$post;                      // code_block_post config override
				}
				else
				{
					return  $matches[2] .               // <code class="foo">
							$this->encode_tags($code) . // the code sample
							$matches[4];                // </code>
				}
			},
			$str
		);
	}

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
					// First line takes care of the line break that might be
					// there, which should be a line break because it is just a
					// simple break from the [code] tag.

					// Note: [div class="codeblock"] has been converted to
					// <div class="codeblock"> at this point
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

	/**
	 * If present we'll run `idn_to_ascii` on the the URL to protect against
	 * homograph attacks.
	 *
	 * @param string $url A URL
	 * @return string A decoded URL
	 */
	public function decodeIDN($url)
	{
		if ( ! function_exists('idn_to_ascii'))
		{
			return $url;
		}

		// Fill in protocol for protocol-relative URLs so that this method
		// always returns a valid URL in the eyes of FILTER_VALIDATE_URL
		if (strpos($url, '//') === 0)
		{
			$scheme = empty($_SERVER['HTTPS']) ? 'http' : 'https';
			$url = $scheme . ':' . $url;
		}

		// Amazingly, this will parse if passed 'http://example.com is fun!'
		// but will not parse if passed 'I really like http://example.com'
		$parts = parse_url($url);

		// According to http://php.net/idn_to_ascii this should only be run
		// on the domain and not the entire string.
		if (isset($parts['host']))
		{
			if (is_php('7.2'))
			{
				$parts['host'] = @idn_to_ascii($parts['host'], 0, defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0);
			}
			else
			{
				$parts['host'] = @idn_to_ascii($parts['host']);
			}
		}

		return $this->unparse_url($parts);
	}

	/**
	 * Copied from http://php.net/manual/en/function.parse-url.php#106731
	 */
	private function unparse_url($parsed_url) {
	  $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
	  $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
	  $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
	  $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
	  $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
	  $pass     = ($user || $pass) ? "$pass@" : '';
	  $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
	  $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
	  $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
	  return "$scheme$user$pass$host$port$path$query$fragment";
	}

}
// END CLASS

// EOF
