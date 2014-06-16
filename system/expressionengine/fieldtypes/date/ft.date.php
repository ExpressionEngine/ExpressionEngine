<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// --------------------------------------------------------------------

/**
 * ExpressionEngine Date Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Date_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Date',
		'version'	=> '1.0'
	);

	var $has_array_data = FALSE;

	function save($data)
	{
		if ( ! is_numeric($data))
		{
			$data = ee()->localize->string_to_timestamp($data);
		}

		if (empty($data))
		{
			$data = 0;
		}

		return $data;
	}

	// --------------------------------------------------------------------

	function grid_save($data)
	{
		if ( ! is_numeric($data))
		{
			$data = ee()->localize->string_to_timestamp($data);
		}

		if ( ! empty($data) && $this->settings['localize'] !== TRUE)
		{
			$data = array($data, ee()->session->userdata('timezone'));
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
			$data = ee()->localize->string_to_timestamp($data);
		}

		if ($data === FALSE)
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

		$is_grid = isset($this->settings['grid_field_id']);

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
			$custom_date = $field_data;
		}
		else
		{
			// primarily handles default expiration, comment expiration, etc.
			// in this context 'offset' is unrelated to localization.
			$offset = isset($this->settings['default_offset']) ? $this->settings['default_offset'] : 0;

			if ( ! $field_data && ! $offset)
			{
				$field_data = $date;

				if (isset($this->settings['always_show_date']) && $this->settings['always_show_date'] == 'y')
				{
					$custom_date = ee()->localize->human_time();
				}
			}
			else	// Everything else
			{
				if (isset($this->settings['field_dt']) AND $this->settings['field_dt'] != '')
				{
					$localize = $this->settings['field_dt'];
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

		$date_js_globals = array(
			'date_format'     => ee()->localize->datepicker_format(),
			'time_format'     => ee()->session->userdata('time_format', ee()->config->item('time_format')),
			'include_seconds' => ee()->session->userdata('include_seconds', ee()->config->item('include_seconds'))
		);

		if (REQ == 'CP')
		{
			ee()->javascript->set_global('date', $date_js_globals);
		}
		elseif ( ! ee()->session->cache(__CLASS__, 'date_js_loaded'))
		{
			// We only want to set the date global once
			ee()->session->set_cache(__CLASS__, 'date_js_loaded', TRUE);
			ee()->javascript->output('EE.date = '.json_encode($date_js_globals).';');
		}

		ee()->cp->add_js_script(array(
			'ui' => 'datepicker',
			'file' => 'cp/date'
		));

		// Note- the JS will automatically localize the default date- but not necessarily in a way we want
		// Hence we adjust default date to compensate for the coming localization
		ee()->javascript->output('
			var d = new Date();
			var jsCurrentUTC = d.getTimezoneOffset()*60;
			var adjustedDefault = 1000*('.$date.'+jsCurrentUTC);

			$("[name='.$this->field_name.']").not(".grid_field_container [name='.$this->field_name.']").datepicker({
				constrainInput: false,
				dateFormat: EE.date.date_format + EE.date_obj_time,
				defaultDate: new Date(adjustedDefault)
			});
		');

		if ( ! ee()->session->cache(__CLASS__, 'grid_js_loaded')
			&& $this->content_type() == 'grid')
		{
			ee()->javascript->output('

				Grid.bind("date", "display", function(cell)
				{
					var d = new Date();
					var jsCurrentUTC = d.getTimezoneOffset()*60;
					var adjustedDefault = 1000*('.$date.'+jsCurrentUTC);

					field = cell.find(".ee_datepicker");
					field.removeAttr("id");

					cell.find(".ee_datepicker").datepicker({
						constrainInput: false,
						dateFormat: EE.date.date_format + EE.date_obj_time,
						defaultDate: new Date(adjustedDefault)
					});
				});

			');

			ee()->session->set_cache(__CLASS__, 'grid_js_loaded', TRUE);
		}

		$input_class = 'ee_datepicker text';

		if ( ! $is_grid)
		{
			$input_class .= ' field';
		}

		$r = form_input(array(
			'name'	=> $this->field_name,
			'value'	=> $custom_date,
			'class'	=> $input_class
		));

		if ( ! in_array($this->field_name, $special))
		{
			$text_direction = (isset($this->settings['field_text_direction']))
				? $this->settings['field_text_direction'] : 'ltr';

			// We hide the dropdown in Grid because the localization setting is
			// effectively global for that field
			if ( ! $is_grid)
			{
				$localized = ( ! isset($_POST[$date_local])) ? (($localize === TRUE) ? 'y' : 'n') : ee()->input->post($date_local, TRUE);

				$localized_opts	= array(
					'y' => ee()->lang->line('localized_date'),
					'n' => ee()->lang->line('fixed_date')
				);

				$r .= NBS.NBS.NBS.NBS;
				$r .= form_dropdown($date_local, $localized_opts, $localized, 'dir="'.$text_direction.'"');
			}
		}

		return $r;
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

			return ee()->localize->format_date(
				$params['format'],
				$date[0],
				$localize
			);
		}

		return $date[0];
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		return array(
			$this->grid_checkbox_row(
				lang('grid_date_localized'),
				'localize',
				'localize',
				isset($data['localize']) ? $data['localize'] : TRUE
			)
		);
	}

	// --------------------------------------------------------------------

	function save_settings($data)
	{
		// Date or relationship types don't need formatting.
		$data['field_fmt'] = 'none';
		$data['field_show_fmt'] = 'n';
		$_POST['update_formatting'] = 'y';

		return $data;
	}

	// --------------------------------------------------------------------

	function grid_save_settings($data)
	{
		return array(
			'localize' => isset($data['localize'])
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
}

// END Date_ft class

/* End of file ft.date.php */
/* Location: ./system/expressionengine/fieldtypes/ft.date.php */