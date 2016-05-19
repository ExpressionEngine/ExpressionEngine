<?php

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;

class Number extends Formatter {

	/**
	 * Format the memory to a sane byte format
	 *
	 * @param  string  $memory  the memory in bytes
	 * @return string  the formatted memory string
	 **/
	public function memory($abbr = TRUE)
	{
		$memory = $this->content;
		$precision = 0;
		$lang_suffix = ($abbr) ? '_abbr' : '';

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
