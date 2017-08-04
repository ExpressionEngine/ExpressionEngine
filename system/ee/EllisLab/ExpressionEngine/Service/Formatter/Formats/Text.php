<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;

/**
 * Formatter\Text
 */
class Text extends Formatter {

	/**
	 * Escapes a string for use in an HTML attribute
	 *
	 * @param bool $double_encode Whether to double encode existing HTML entities
	 * @return self This returns a reference to itself
	 */
	public function attributeEscape($double_encode = FALSE)
	{
		$this->content = htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8', $double_encode);
		return $this;
	}

	/**
	 * Makes content safe to use in an HTML attribute. In addition to escaping like attributeEscape(),
	 * it allows for character limiting, and unicode punctuation—handy for meta tags where entities may not be parsed.
	 *
	 * @param  array  $options Options: (bool) double_encode, (string) end_char, (int) limit, (bool) unicode_punctuation
	 * @return self This returns a reference to itself
	 */
	public function attributeSafe($options = [])
	{
		$options = [
			'double_encode'       => (isset($options['double_encode'])) ? get_bool_from_string($options['double_encode']) : FALSE,
			'end_char'            => (isset($options['end_char'])) ? $options['end_char'] : '&#8230;',
			'limit'               => (isset($options['limit'])) ? (int) $options['limit'] : FALSE,
			'unicode_punctuation' => (isset($options['unicode_punctuation'])) ? get_bool_from_string($options['unicode_punctuation']) : TRUE,
		];

		// syntax highlighted code will be one long "word" and not summarizable
		if (strpos($this->content, '<div class="codeblock">') !== FALSE)
		{
			$this->content = preg_replace('|<div class="codeblock">.*?</div>|is', '', $this->content);
		}

		if ($options['unicode_punctuation'])
		{
			$punctuation = [
				'&#8217;' => '’', // right single curly
				'&#8216;' => '‘', // left single curly
				'&#8221;' => '”', // right double curly
				'&#8220;' => '“', // left double curly
				'&#8212;' => '—', // em-dash
				'&#8230;' => '…', // ellipses
				'&nbsp;'  => ' '
			];

			$this->content = str_replace(array_keys($punctuation), array_values($punctuation), $this->content);

			// flip end_char too if set to the default
			$options['end_char'] = (isset($punctuation[$options['end_char']])) ? $punctuation[$options['end_char']] : $options['end_char'];
		}

		$this->content = strip_tags($this->content);
		$this->attributeEscape($options['double_encode']);

		if (is_numeric($options['limit']))
		{
			$this->limitChars(['characters' => $options['limit'], 'end_char' => $options['end_char']]);

			// keep whole words only
			while (strlen($this->content) > $options['limit'])
			{
				$words = explode(' ', $this->content);
				array_pop($words);
				$this->content = implode(' ', $words).$options['end_char'];
			}
		}

		return $this;
	}

	/**
	 * Make a URL slug from the text
	 *
	 * @param  array  $options Options: (string) separator, (bool) lowercase
	 * @return self This returns a reference to itself
	 */
	public function urlSlug($options = [])
	{
		if ( ! isset($options['separator']))
		{
			$options['separator'] = (ee()->config->item('word_separator') == 'underscore') ? '_' : '-';
		}

		$lowercase = (isset($options['lowercase']) && $options['lowercase'] === FALSE) ? FALSE : TRUE;

		$this->accentsToAscii();

		// order here is important
		$replace = [
			// remove numeric entities
			'#&\#\d+?;#i' => '',
			// remove named entities
			'#&\S+?;#i' => '',
			// replace whitespace and forward slashes with the separator
			'#\s+|/+#i' => $options['separator'],
			// only allow low ascii letters, numbers, dash, dot, and underscore
			'#[^a-z0-9\-\._]#i' => '',
			// no dot-then-separator (in case multiple sentences were passed)
			'#\.'.$options['separator'].'#i' => $options['separator'],
			// reduce multiple instances of the separator to a single
			'#'.$options['separator'].'+#i' => $options['separator'],
		];

		$this->content = strip_tags($this->content);
		$this->content = preg_replace(array_keys($replace), array_values($replace), $this->content);

		// don't allow separators or dots at the beginning or end of the string, and remove slashes if they exist
		$this->content = trim(stripslashes($this->content), '-_.');

		if ($lowercase === TRUE)
		{
			$this->content = strtolower($this->content);
		}

		return $this;
	}

	/**
	 * Converts accented / multi-byte characters, e.g. ü, é, ß to ASCII transliterations
	 * Uses foreign_chars.php config, either the default or user override, as a map
	 *
	 * @return self This returns a reference to itself
	 */
	public function accentsToAscii()
	{
		$accent_map = ee()->config->loadFile('foreign_chars');

		if (empty($accent_map))
		{
			return $this;
		}

		$this->content = utf8_decode($this->content);
		$chars = preg_split('//', $this->content, NULL, PREG_SPLIT_NO_EMPTY);

		foreach ($chars as $index => $char)
		{
			$ord = ord($char);
			if (isset($accent_map[$ord]))
			{
				$this->content[$index] = $accent_map[$ord];
			}
		}

		return $this;
	}

	/**
	 * Censor naughty words, respects application preferences
	 *
	 * @return self This returns a reference to itself
	 */
	public function censor()
	{
		$censored = ee()->session->cache(__CLASS__, 'censored_words');

		// setup censored words regex
		if ( ! is_array($censored))
		{
			$censored = ee()->config->item('censored_words');

			if (empty($censored))
			{
				ee()->session->set_cache(__CLASS__, 'censored_words', []);
				return $this;
			}

			$censored = preg_split('/[\n|\|]/', $censored, NULL, PREG_SPLIT_NO_EMPTY);

			foreach ($censored as $key => $bad)
			{
				$length = strlen($bad);
				$bad = '/\b('.preg_quote($bad, '/').')\b/ui';

				// wildcards
				$censored[$key] = str_replace('\*', '(\w*)', $bad);
			}

			ee()->session->set_cache(__CLASS__, 'censored_words', $censored);
		}

		$replace = ee()->config->item('censor_replacement');

		foreach ($censored as $bad)
		{
			if ($replace)
			{
				$this->content = preg_replace($bad, $replace, $this->content);
			}
			else
			{
				$this->content = preg_replace_callback($bad,
					function($matches)
					{
						return str_repeat('#', strlen($matches[0]));
					},
					$this->content
				);
			}

		}

		return $this;
	}

	/**
	 * Limit to X characters, with an optional end character
	 *
	 * @param  array  $options Options: (int) characters, (string) end_char
	 * @return self This returns a reference to itself
	 */
	public function limitChars($options = [])
	{
		$limit = (isset($options['characters'])) ? (int) $options['characters'] : 500;
		$end_char = (isset($options['end_char'])) ? $options['end_char'] : '&#8230;';
		$this->content = strip_tags($this->content);

		if (strlen($this->content) < $limit)
		{
			return $this;
		}

		$this->content = preg_replace(
			"/\s+/",
			' ',
			str_replace(
				array("\r\n", "\r", "\n"),
				' ',
				$this->content
			)
		);

		if (strlen($this->content) <= $limit)
		{
			return $this;
		}

		$cut = substr($this->content, 0, $limit);
		$this->content = (strlen($cut) == strlen($this->content)) ? $cut : $cut.$end_char;

		return $this;
	}

	/**
	 * Preps the content for use in a form field
	 *
	 * @return self This returns a reference to itself
	 */
	public function formPrep()
	{
		ee()->load->helper('form');
		$this->content = form_prep($this->content);
		return $this;
	}

	/**
	 * Encrypt the text
	 *
	 * @param  array  $options Options: (string) key, (bool) encode
	 * @return self This returns a reference to itself
	 */
	public function encrypt($options = [])
	{
		$key = (isset($options['key'])) ? $options['key'] : NULL;

		if (isset($options['encode']) && get_bool_from_string($options['encode']))
		{
			$this->content = ee('Encrypt', $key)->encode($this->content);
		}
		else
		{
			$this->content = ee('Encrypt', $key)->encrypt($this->content);
		}

		return $this;
	}

	/**
	 * Encode ExpressionEngine Tags. By default encodes all curly braces so variables are also protected.
	 *
	 * @param  array  $options Options: (bool) encode_vars
	 * @return self This returns a reference to itself
	 */
	public function encodeEETags($options = [])
	{
		$encode_vars = (isset($options['encode_vars'])) ? $options['encode_vars'] : TRUE;

		if ($this->content != '' && strpos($this->content, '{') !== FALSE)
		{
			if ($encode_vars === TRUE)
			{
				$this->content = str_replace(array('{', '}'), array('&#123;', '&#125;'), $this->content);
			}
			else
			{
				$this->content = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $this->content);
				$this->content = str_replace(array('{exp:', '{/exp'), array('&#123;exp:', '&#123;\exp'), $this->content);
				$this->content = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $this->content);
				$this->content = preg_replace("/\{path:(.+?)\}/", "&#123;path:\\1&#125;", $this->content);
				$this->content = preg_replace("/\{redirect=(.+?)\}/", "&#123;redirect=\\1&#125;", $this->content);
				$this->content = str_replace(array('{if', '{/if'), array('&#123;if', '&#123;/if'), $this->content);
				$this->content = preg_replace("/\{layout:(.+?)\}/", "&#123;layout:\\1&#125;", $this->content);
			}
		}

		return $this;
	}

	/**
	 * Get the length of the string
	 *
	 * @return self This returns a reference to itself
	 */
	public function getLength()
	{
		$this->content = (extension_loaded('mbstring')) ? mb_strlen($this->content, 'utf8') : strlen($this->content);
		return $this;
	}

	/**
	 * Converts all applicable characters to HTML entities
	 *
	 * @return self This returns a reference to itself
	 */
	public function convertToEntities()
	{
		$this->content = htmlentities($this->content, ENT_QUOTES, 'UTF-8');
		return $this;
	}
}

// EOF
