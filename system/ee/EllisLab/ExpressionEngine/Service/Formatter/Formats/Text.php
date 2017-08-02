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

	public function attributeSafe($double_encode = FALSE, $unicode_punctuation = TRUE, $limit = FALSE, $end_char = '&#8230;')
	{
		// syntax highlighted code will be one long "word" and not summarizable
		if (strpos($this->content, '<div class="codeblock">') !== FALSE)
		{
			$this->content = preg_replace('|<div class="codeblock">.*?</div>|is', '', $this->content);
		}

		if ($unicode_punctuation)
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
			$end_char = (isset($punctuation[$end_char])) ? $punctuation[$end_char] : $end_char;
		}

		$this->content = strip_tags($this->content);
		$this->attributeEscape($double_encode);

		if (is_numeric($limit))
		{
			$this->limitChars($limit, $end_char);

			// keep whole words only
			while (strlen($this->content) > $limit)
			{
				$words = explode(' ', $this->content);
				array_pop($words);
				$this->content = implode(' ', $words).$end_char;
			}
		}

		return $this;
	}

	public function limitChars($limit = 500, $end_char = '&#8230;')
	{
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

		$out = '';
		foreach (explode(' ', trim($this->content)) as $val)
		{
			$out .= $val.' ';

			if (strlen($out) >= $limit)
			{
				$out = trim($out);
				$this->content = (strlen($out) == strlen($this->content)) ? $out : $out.$end_char;
				return $this;
			}
		}

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
