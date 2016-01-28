<?php
namespace EllisLab\ExpressionEngine\Service\Filter;

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\View\ViewFactory;

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
 * ExpressionEngine Date Filter Class
 *
 * This will provide the HTML for a filter that will list a set of "in the last
 * <<period>>" options as well as a custom <input> element for a specific date.
 * That <input> element will trigger a JS date picker to assist which will
 * ensure the date is correctly formatted.
 *
 * This will also interpret incoming date strings and will convert them to a
 * UNIX timestamp for use in the value() method.
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Date extends Filter {

	/**
	 * @var int The unix timestamp value of the filter
	 */
	private $timestamp;

	/**
	 * @todo inject $date_format (removes session & config dedpencies)
	 * @todo inject ee()->localize (for string_to_timestamp and format_date)
	 * @todo inject ee()->javascript (for set_global)
	 * @todo inject ee()->cp (for ee()->cp->add_js_script)
	 */
	public function __construct()
	{
		$this->name = 'filter_by_date';
		$this->label = 'date';
		$this->placeholder = lang('custom_date');
		$this->options = array(
			'86400'     => ucwords(lang('last').' 24 '.lang('hours')),
			'604800'    => ucwords(lang('last').' 7 '.lang('days')),
			'2592000'   => ucwords(lang('last').' 30 '.lang('days')),
			'15552000'  => ucwords(lang('last').' 180 '.lang('days')),
			'31536000'  => ucwords(lang('last').' 365 '.lang('days')),
		);

		$date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));

		ee()->lang->loadfile('calendar');

		ee()->javascript->set_global('date.date_format', $date_format);
		ee()->javascript->set_global('lang.date.months.full', array(
			lang('cal_january'),
			lang('cal_february'),
			lang('cal_march'),
			lang('cal_april'),
			lang('cal_may'),
			lang('cal_june'),
			lang('cal_july'),
			lang('cal_august'),
			lang('cal_september'),
			lang('cal_october'),
			lang('cal_november'),
			lang('cal_december')
		));
		ee()->javascript->set_global('lang.date.months.abbreviated', array(
			lang('cal_jan'),
			lang('cal_feb'),
			lang('cal_mar'),
			lang('cal_apr'),
			lang('cal_may'),
			lang('cal_june'),
			lang('cal_july'),
			lang('cal_aug'),
			lang('cal_sept'),
			lang('cal_oct'),
			lang('cal_nov'),
			lang('cal_dec')
		));
		ee()->javascript->set_global('lang.date.days', array(
			lang('cal_su'),
			lang('cal_mo'),
			lang('cal_tu'),
			lang('cal_we'),
			lang('cal_th'),
			lang('cal_fr'),
			lang('cal_sa'),
		));
		ee()->cp->add_js_script(array(
			'file' => array('cp/date_picker'),
		));

		$value = $this->value();
		if ($value && ! array_key_exists($value, $this->options))
		{
			$date = ee()->localize->string_to_timestamp($value);
			$this->timestamp = $date;
			$this->display_value = ee()->localize->format_date($date_format, $date);
			$this->selected_value = array($date, $date+86400);
		}
	}

	/**
	 * Validation:
	 *   - if the value of the filter is in the options then it is valid
	 *   - if not and the value is an integer, then it is valid
	 *   - otherwise it is invalid
	 */
	public function isValid()
	{
		$value = $this->value();
		if (array_key_exists($value, $this->options))
		{
			return TRUE;
		}

		if (is_int($value))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @see Filter::render
	 *
	 * Overriding the abstract class's render method in order to pass in the
	 * timestamp value to a custom 'date' view
	 */
	public function render(ViewFactory $view, URL $url)
	{
		$options = $this->prepareOptions($url);

		if (empty($options))
		{
			return;
		}

		$value = $this->display_value;
		if (is_null($value))
		{
			$value = (array_key_exists($this->value(), $this->options)) ?
				$this->options[$this->value()] :
				$this->value();
		}

		$filter = array(
			'label'			=> $this->label,
			'name'			=> $this->name,
			'value'			=> $value,
			'custom_value'  => (array_key_exists($this->name, $_POST)) ? $_POST[$this->name] : FALSE,
			'placeholder'	=> $this->placeholder,
			'options'		=> $options,
			'timestamp'		=> $this->timestamp
		);
		return $view->make('_shared/filters/date')->render($filter);
	}

}

// EOF
