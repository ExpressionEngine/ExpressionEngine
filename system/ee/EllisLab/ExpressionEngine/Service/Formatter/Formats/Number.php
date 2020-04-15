<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;
use NumberFormatter;

/**
 * Formatter\Number
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

	/**
	 * Currency Formatter
	 *
	 * Greatest accuracy requires the PHP intl extension to be available
	 *
	 * @param  array  $options (string) currency, (string) locale
	 * @return self This returns a reference to itself
	 */
	public function currency($options = [])
	{
		$options = [
			'currency' => (isset($options['currency'])) ? $options['currency'] : 'USD',
			'locale' => (isset($options['locale'])) ? $options['locale'] : 'en_US.UTF-8',
			'decimals' => (isset($options['decimals'])) ? (int) $options['decimals'] : NULL,
		];

		// best option, will display the currency correctly based on the locale
		// e.g. $112,358.13 and €112,358.13 in the US; 112.358,13 $ and 112.358,13 € in Germany
		if ($this->intl_loaded)
		{
			$fmt = new \NumberFormatter($options['locale'], \NumberFormatter::CURRENCY);

			if (is_int($options['decimals']))
			{
				$fmt->setAttribute($fmt::FRACTION_DIGITS, $options['decimals']);
			}

			$this->content = $fmt->formatCurrency((float) $this->content, $options['currency']);
			return $this;
		}

		// This PHP function is a wrapper for strfmon, so isn't available on all systems, e.g. Windows
		// Won't get the position of the currency marker correct for non-US locales.
		// This is intentionally a 20% effort, 80% solution situation rather than maintaining our own
		// localization formatting lookup tables. The 100% solution is easily achieved by ensuring
		// that the intl extension is loaded in PHP, handled above.
		// NOTE: `money_format` is deprecated in PHP7.4
		if (function_exists('money_format'))
		{
			// grab the current monetary locale to reset after formatting
			$sys_locale = setlocale(LC_MONETARY, 0);

			// set the monetary locale to the specified option
			setlocale(LC_MONETARY, $options['locale']);

			$right_precision = (is_int($options['decimals'])) ? $options['decimals'] : 2;
			$this->content = money_format("%.{$right_precision}n", (float) $this->content);

			// set the monetary locale back to normal
			setlocale(LC_MONETARY, $sys_locale);
			return $this;
		}

		throw new \Exception('<code>{...:currency}</code> modifier error: Environment does not support any known currency formatters, please install the PHP <b>intl</b> extension.');
	}

	/**
	 * Duration Formatter
	 *
	 * @param  array  $options (string) locale
	 * @return self This returns a reference to itself
	 */
	public function duration($options = [])
	{
		$this->content = round($this->content);

		$options = [
			'locale' => (isset($options['locale'])) ? $options['locale'] : 'en_US.UTF-8',
		];

		if ($this->intl_loaded)
		{
			$fmt = new \NumberFormatter($options['locale'], \NumberFormatter::DURATION);
			$this->content = $fmt->format($this->content);
			return $this;
		}

		// the following is a fallback that follows the NumberFormatter::DURATION
		// output pattern if the intl extension isn't available

		if ($this->content < 60)
		{
			$this->content = sprintf(lang('formatter_duration_seconds_only'), $this->content);
			return $this;
		}

		$seconds = $this->content % 60;

		// NumberFormatter::DURATION zero pads everything but the left-most digit
		if ($seconds < 10)
		{
			$seconds = '0'.$seconds;
		}

		$remainder = ($this->content - $seconds) / 60;
		$minutes = $remainder % 60;

		$remainder = $remainder - $minutes;

		if ($remainder <= 0)
		{
			$this->content = $minutes.':'.$seconds;
			return $this->content;
		}

		if ($minutes < 10)
		{
			$minutes = '0'.$minutes;
		}

		$remainder = $remainder / 60;
		$hours = number_format($remainder);

		$this->content = $hours.':'.$minutes.':'.$seconds;
		return $this;
	}

	/**
	 * Number Format Formatter
	 *
	 * Formats a number with typical options
	 *
	 * @param  array  $options (int) decimals, (string) decimal_point, (string) thousands_separator
	 * @return self This returns a reference to itself
	 */
	public function number_format($options = [])
	{
		$options = [
			'decimals' => (isset($options['decimals'])) ? (int) $options['decimals'] : 0,
			'decimal_point' => (isset($options['decimal_point'])) ? $options['decimal_point'] : '.',
			'thousands_separator' => (isset($options['thousands_separator'])) ? $options['thousands_separator'] : ',',
		];

		$this->content = number_format(
			(float) $this->content,
			$options['decimals'],
			$options['decimal_point'],
			$options['thousands_separator']
		);

		return $this;
	}

	/**
	 * Ordinal Formatter
	 *
	 * Locales other than English require the intl extension
	 *
	 * @param  array  $options (string) locale
	 * @return self This returns a reference to itself
	 */
	public function ordinal($options = [])
	{
		$options = [
			'locale' => (isset($options['locale'])) ? $options['locale'] : 'en_US.UTF-8',
		];

		if ($this->intl_loaded)
		{
			$fmt = new \NumberFormatter($options['locale'], \NumberFormatter::ORDINAL);
			$this->content = $fmt->format($this->content);
			return $this;
		}

		// fallback will only work for English ordinal indicators
		$indicators = ['th','st','nd','rd','th','th','th','th','th','th'];

		$mod = (int) $this->content % 100;
		if (($mod >= 11) && ($mod <= 13))
		{
			$indicator = $indicators[0];
		}
		else
		{
			$indicator = $indicators[(int) $this->content % 10];
		}

		$this->content = number_format((float) $this->content).$indicator;
		return $this;
	}

	/**
	 * Spell Out Formatter
	 *
	 * Requires the PHP intl extension to be available
	 *
	 * @param  array  $options (string) capitalize, (string) locale
	 * @return self This returns a reference to itself
	 */
	public function spellout($options = [])
	{
		if ( ! $this->intl_loaded)
		{
			throw new \Exception('<code>{...:spellout}</code> modifier error: This modifier requires the PHP <b>intl</b> extension to be installed.');
		}

		$options = [
			'capitalize' => (isset($options['capitalize'])) ? $options['capitalize'] : FALSE,
			'locale' => (isset($options['locale'])) ? $options['locale'] : 'en_US.UTF-8',
		];

		$fmt = new \NumberFormatter($options['locale'], \NumberFormatter::SPELLOUT);
		$this->content = $fmt->format($this->content);

		switch ($options['capitalize'])
		{
			case 'ucfirst':
				$this->content = ucfirst($this->content);
				break;
			case 'ucwords':
				$this->content = ucwords($this->content);
				break;
			default:
				// nada
		}

		return $this;
	}
}

// EOF
