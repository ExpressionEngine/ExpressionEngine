<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Date_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Date',
		'version'	=> '1.0'
	);

	var $has_array_data = FALSE;

	
	function save($data)
	{
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
		if ( ! is_numeric($data))
		{
			$data = $this->EE->localize->convert_human_date_to_gmt($data);
		}
		
		if ( ! is_numeric($data) && $data != '')
		{
			return lang('invalid_date');
		}

		return TRUE;
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

		$date_field = $this->field_name;
		$date_local = 'field_offset_'.$this->field_id;

		$date = $this->EE->localize->set_localized_time();
		$custom_date = '';

		$localize = FALSE;
		
		if (isset($_POST[$date_field]) && ! is_numeric($_POST[$date_field])) // string in $_POST, probably had a validation error
		{
			if ($_POST[$date_field])
			{
				// human readable data - convert cal date back to gmt
				$custom_date = $_POST[$date_field];
				$date = $this->EE->localize->convert_human_date_to_gmt($custom_date);
			}

			if ( ! is_numeric($date))
			{
				// don't output JS that tries to do math with the English error from convert_human_date_to_gmt()
				$date = 0;
			}
		}
		else
		{
			$offset = isset($this->settings['default_offset']) ? $this->settings['default_offset'] : 0;
			
			if ( ! $field_data && ! $offset)
			{
				$field_data = $date;
				
				if (isset($this->settings['always_show_date']) && $this->settings['always_show_date'] == 'y')
				{
					$custom_date = $this->EE->localize->set_human_time($field_data, $localize);
				}
			}
			else	// Everything else
			{
				$localize = TRUE;

				if (isset($this->settings['field_dt']))
				{
					// Are we dealing with a fixed date?
					if ($this->settings['field_dt'] != '')
					{
						$field_data = $this->EE->localize->simpl_offset($field_data, $this->settings['field_dt']);
						$localize = FALSE;
					}
				}
				elseif ( ! $field_data && $offset)
				{
					$localize = FALSE;
					$field_data = $date + $offset;
				}
				
				// doing it in here so that if we don't have field_data
				// the field doesn't get populated, but the calendar still
				// shows the correct default.
				if ($field_data)
				{
					$custom_date = $this->EE->localize->set_human_time($field_data, $localize);
				}
			}

			$date = $this->EE->localize->set_localized_time($field_data);
		}
		
		// 1 second = 1000 milliseconds
		$cal_date = $date * 1000;

		// Note- the JS will automatically localize the default date- but not necessarily in a way we want
		// Hence we adjust default date to compensate for the coming localization
		$this->EE->javascript->output('
			var d = new Date();
			var jsCurrentUTC = d.getTimezoneOffset()*60;
			var adjustedDefault = 1000*('.$date.'+jsCurrentUTC);
		
			$("#'.$this->field_name.'").datepicker({dateFormat: $.datepicker.W3C + EE.date_obj_time, defaultDate: ('.$cal_date.' == 0) ? new Date() : new Date(adjustedDefault)});
		');

		$r = form_input(array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_name,
			'value'	=> $custom_date,
			'class'	=> 'field'
		));

		if ( ! in_array($this->field_name, $special))
		{
			$localized = ( ! isset($_POST[$date_local])) ? (($localize == FALSE) ? 'n' : 'y') : $_POST[$date_local];

			$localized_opts	= array(
				'y' => $this->EE->lang->line('localized_date'),
				'n' => $this->EE->lang->line('fixed_date')
			);

			$r .= NBS.NBS.NBS.NBS;
			$r .= form_dropdown($date_local, $localized_opts, $localized, 'dir="'.$this->settings['field_text_direction'].'" id="'.$date_local.'"');
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
		// if we're here, they're just using the date field without formatting, e.g. {custom_date}
		return $date;
	}

	function save_settings($data)
	{
		// Date or relationship types don't need formatting.
		$data['field_fmt'] = 'none';
		$data['field_show_fmt'] = 'n';
		$_POST['update_formatting'] = 'y';
		
		return $data;
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
			'constraint'	=> 8
			);			
		
		return $fields;
	}	
}

// END Date_ft class

/* End of file ft.date.php */
/* Location: ./system/expressionengine/fieldtypes/ft.date.php */