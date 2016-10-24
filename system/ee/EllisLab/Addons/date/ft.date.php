<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Date Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Date_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Date',
		'version'	=> '1.0.0'
	);

	var $has_array_data = FALSE;

	/**
	 * Parses the date input, first with the configured date format (as used
	 * by the datepicker). If that fails it will try again with a fuzzier
	 * conversion, which allows things like "2 weeks".
	 *
	 * @param	string	$date	A date string for parsing
	 * @return	mixed	Will return a UNIX timestamp or FALSE
	 */
	private function _parse_date($date)
	{
		// First we try with the configured date format
		$timestamp = ee()->localize->string_to_timestamp($date, TRUE, ee()->localize->get_date_format());

		// If the date format didn't work, try something more fuzzy
		if ($timestamp === FALSE)
		{
			$timestamp = ee()->localize->string_to_timestamp($date);
		}

		return $timestamp ?: NULL;
	}

	// --------------------------------------------------------------------

	function save($data)
	{
		if ( ! is_numeric($data))
		{
			$data = $this->_parse_date($data);
		}

		return $data;
	}

	// --------------------------------------------------------------------

	function grid_save($data)
	{
		if ( ! is_numeric($data))
		{
			$data = $this->_parse_date($data);
		}

		if ( ! empty($data) && $this->settings['localize'] !== TRUE)
		{
			$data = array($data, ee()->session->userdata('timezone', ee()->config->item('default_site_timezone')));
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate Field
	 *
	 * @param 	string
	 * @return	mixed
	 */
	function validate($data)
	{
		if ( ! is_numeric($data) && trim($data) && ! empty($data))
		{
			$data = $this->_parse_date($data);
		}

		if ($data === FALSE
			OR (is_numeric($data) && ($data > 2147483647 OR $data < -2147483647)))
		{
			return lang('invalid_date');
		}

		return array('value' => $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field
	 *
	 * @param 	array
	 */
	function display_field($field_data)
	{
		$special = array('entry_date', 'expiration_date', 'comment_expiration_date');

		if ( ! is_numeric($field_data))
		{
			ee()->load->helper('custom_field_helper');

			$data = decode_multi_field($field_data);

			// Grid field stores timestamp and timezone in one field
			if ( ! empty($data) && isset($data[1]))
			{
				$field_data = $data[0];
				$this->settings['field_dt'] = $data[1];
			}
		}

		$date_field = $this->field_name;
		$date_local = 'field_offset_'.$this->field_id;

		$date = ee()->localize->now;
		$custom_date = '';
		$localize = TRUE;

		if ((isset($_POST[$date_field]) && ! is_numeric($_POST[$date_field]))
			OR ( ! is_numeric($field_data) && ! empty($field_data)))
		{
			// probably had a validation error so repopulate as-is
			$custom_date = isset($_POST[$date_field]) ? $_POST[$date_field] : $field_data;
		}
		else
		{
			// primarily handles default expiration, comment expiration, etc.
			// in this context 'offset' is unrelated to localization.
			$offset = $this->get_setting('default_offset', 0);

			if ( ! $field_data && ! $offset)
			{
				$field_data = $date;

				if ($this->get_setting('always_show_date'))
				{
					$custom_date = ee()->localize->human_time();
				}
			}
			else	// Everything else
			{
				$field_dt = $this->get_setting('field_dt');
				if ( ! empty($field_dt))
				{
					$localize = $field_dt;
				}

				if ( ! $field_data && $offset)
				{
					$field_data = $date + $offset;
				}

				// doing it in here so that if we don't have field_data
				// the field doesn't get populated, but the calendar still
				// shows the correct default.
				if ($field_data)
				{
					$custom_date = ee()->localize->human_time($field_data, $localize);
				}
			}

			$date = $field_data;
		}

		ee()->lang->loadfile('calendar');

		ee()->javascript->set_global('date.date_format', ee()->localize->get_date_format());
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
			lang('cal_sep'),
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

		$localized = ( ! isset($_POST[$date_local])) ? (($localize === TRUE) ? 'y' : 'n') : ee()->input->post($date_local, TRUE);

		return ee('View')->make('date:publish')->render(array(
			'has_localize_option' => ( ! in_array($this->field_name, $special) && $this->content_type() != 'grid'),
			'field_name' => $this->field_name,
			'value' => $custom_date,
			'localize_option_name' => $date_local,
			'localized' => $localized,
			'date_format' => ee()->localize->get_date_format(),
			'disabled' => $this->get_setting('field_disabled')
		));
	}

	// --------------------------------------------------------------------

	function pre_process($data)
	{
		return $data;
	}

	// --------------------------------------------------------------------

	function replace_tag($date, $params = array(), $tagdata = FALSE)
	{
		$localize = TRUE;
		if (isset($this->row['field_dt_'.$this->name]) AND $this->row['field_dt_'.$this->name] != '')
		{
			$localize = $this->row['field_dt_'.$this->name];
		}

		return ee()->TMPL->process_date($date, $params, FALSE, $localize);
	}

	// --------------------------------------------------------------------

	function replace_relative($date, $params = array(), $tagdata = FALSE)
	{
		$localize = TRUE;
		if (isset($this->row['field_dt_'.$this->name]) AND $this->row['field_dt_'.$this->name] != '')
		{
			$localize = $this->row['field_dt_'.$this->name];
		}

		return ee()->TMPL->process_date($date, $params, TRUE, $localize);
	}

	// --------------------------------------------------------------------

	public function grid_replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		ee()->load->helper('custom_field_helper');
		$date = decode_multi_field($data);

		if ( ! isset($date[0]))
		{
			return '';
		}

		if (isset($params['format']))
		{
			$localize = TRUE;

			if ($this->settings['localize'] !== TRUE && isset($date[1]))
			{
				$localize = $date[1];
			}

			return ee()->TMPL->process_date($date[0], $params, FALSE, $localize);
		}

		return $date[0];
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		return array(
			'field_options' => array(
				array(
					'title' => 'localize_date',
					'desc' => sprintf(lang('localize_date_desc'), ee('CP/URL')->make('settings/general')),
					'fields' => array(
						'localize' => array(
							'type' => 'yes_no',
							'value' => isset($data['localize']) ? $data['localize'] : TRUE,
						)
					)
				)
			)
		);
	}

	// --------------------------------------------------------------------

	function grid_save_settings($data)
	{
		return array(
			'localize' => get_bool_from_string($data['localize'])
		);
	}

	// --------------------------------------------------------------------

	function settings_modify_column($data)
	{
		$fields['field_id_'.$data['field_id']] = array(
			'type' 			=> 'INT',
			'constraint'	=> 10,
			'default'		=> 0
		);

		$fields['field_dt_'.$data['field_id']] = array(
			'type' 			=> 'VARCHAR',
			'constraint'	=> 50
		);

		return $fields;
	}

	// --------------------------------------------------------------------

	public function grid_settings_modify_column($data)
	{
		return array('col_id_'.$data['col_id'] =>
			array(
				'type' 			=> 'VARCHAR',
				'constraint'	=> 60,
				'default'		=> NULL
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @return bool   Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}
}

// END Date_ft class

// EOF
