<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Date_ft()
	{
		parent::EE_Fieldtype();
	}
	
	// --------------------------------------------------------------------
	
	function save($data)
	{
		// @todo
		return $data;
	}
	
	// --------------------------------------------------------------------

	function validate($data)
	{
		// @todo check for valid date
	}
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		$date_field = $this->field_name;
		$date_local = 'field_dt_'.$this->field_id;
		
		$localize = TRUE;
		$edit = FALSE;
/*

		$custom_date = '';

		if ($error == TRUE)
		{
			$localize = TRUE;

			if ($field_data != '' AND isset($result))
			{
				if (isset($resrow[$date_local]) AND $resrow[$date_local] != '')
				{
					$field_data = $this->EE->localize->offset_entry_dst($field_data, $dst_enabled);
					$field_data = $this->EE->localize->simpl_offset($field_data, $resrow[$date_local]);
					$localize = FALSE;
				}
			}

			if ($field_data != '')
			{
				$custom_date = $this->EE->localize->set_human_time($field_data, $localize);
			}

			$cal_date = ($this->EE->localize->set_localized_time($field_data) * 1000);
		}
		else
		{
			
		}
*/
		$custom_date = ( ! $data) ? '' : $data;
		$cal_date = ($custom_date != '') ? ($this->EE->localize->set_localized_time($this->EE->localize->convert_human_date_to_gmt($custom_date)) * 1000) : ($this->EE->localize->set_localized_time() * 1000);

		$custom_date = ( ! $data) ? '' : $this->EE->localize->set_human_time($data);
		$cal_date = ( ! $data) ? $this->EE->localize->set_localized_time() * 1000 : ($this->EE->localize->set_localized_time($data) * 1000);

		$this->EE->javascript->output('
			$("#'.$this->field_name.'").datepicker({ dateFormat: $.datepicker.W3C + EE.date_obj_time, defaultDate: new Date('.$cal_date.') });
		');
		
		$loc_field = 'field_offset_'.$this->field_id;
		$localized = ( ! isset($_POST[$loc_field])) ? (($localize == FALSE) ? 'n' : 'y') : $_POST[$loc_field];

		$localized_opts	= array(
			'y' => $this->EE->lang->line('localized_date'),
			'n' => $this->EE->lang->line('fixed_date')
		);
		
		$r = form_input(array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_name,
			'value'	=> $custom_date
		));
		
		$r .= NBS.NBS.NBS.NBS;
		$r .= form_dropdown($loc_field, $localized_opts, $localized, 'dir="'.$this->settings['field_text_direction'].'" id="'.$loc_field.'"');
		
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
}

// END Date_ft class

/* End of file ft.date.php */
/* Location: ./system/expressionengine/fieldtypes/ft.date.php */