<?php

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Formatter\Number Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Number extends Formatter {

	/**
	 * Format the memory to a sane byte format
	 *
	 * @param  bool $abbr Use the abbreviated form of the byte format
	 * @param  bool $include_markup Output with <abbr> HTML. Only affects abbreviated forms.
	 * @return self This returns a reference to itself
	 **/
	public function bytes($abbr = TRUE, $include_markup = TRUE)
	{
		$memory = $this->content;
		$precision = 0;

		if ($abbr && $include_markup)
		{
			$lang_suffix = '_abbr_html';
		}
		elseif ($abbr)
		{
			$lang_suffix = '_abbr';
		}
		else
		{
			$lang_suffix = '';
		}

		if ($memory >= 1000000000)
		{
			$precision = 2;
			$memory = round($memory / 1073741824, $precision);
			$unit = lang('formatter_gigabytes'.$lang_suffix);
		}
		elseif ($memory >= 1000000)
		{
			$precision = 1;
			$memory = round($memory / 1048576, $precision);
			$unit = lang('formatter_megabytes'.$lang_suffix);
		}
		elseif ($memory >= 1000)
		{
			$memory = round($memory / 1024);
			$unit = lang('formatter_kilobytes'.$lang_suffix);
		}
		else
		{
			$unit = lang('formatter_bytes'.$lang_suffix);
		}

		$unit = ($abbr) ? $unit : ' '.$unit;
		$this->content = number_format($memory, $precision).$unit;
		return $this;
	}
}

// EOF
