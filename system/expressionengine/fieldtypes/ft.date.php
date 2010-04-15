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
		// @todo probably shouldn't be hard coded in the long run
		$special = array('entry_date', 'expiration_date', 'comment_expiration_date');

		$date_field = $this->field_name;
		$date_local = 'field_dt_'.$this->field_id;

		$date = $this->EE->localize->set_localized_time($data);
		$custom_date = '';
		
		$localize = FALSE;
		
	
		if (isset($_POST[$date_field]))	// Validation failed or autosave - human readable data
		{
			if ($_POST[$date_field])
			{
				$custom_date = $_POST[$date_field];
				$date = $this->EE->localize->convert_human_date_to_gmt($custom_date);
			}
		}
		elseif ( ! $data && isset($this->settings['default_offset']))	// Initial load - no data and showing a field (no offset == blank)
		{
			$date = $this->EE->localize->set_localized_time($data) + $this->settings['default_offset'];
			$custom_date = $this->EE->localize->set_human_time($custom_date);
		}
		
		elseif ($data)	// Everything else
		{
			$date = $this->EE->localize->set_localized_time($data);
			$custom_date = $this->EE->localize->set_human_time($date);
		}
		
		$cal_date = $date * 1000;
		
		$this->EE->javascript->output('
			$("#'.$this->field_name.'").datepicker({ dateFormat: $.datepicker.W3C + EE.date_obj_time , defaultDate: new Date('.$cal_date.') });
		');
		
		$r = form_input(array(
			'name'	=> $this->field_name,
			'id'	=> $this->field_name,
			'value'	=> $custom_date,
			'class'	=> 'field'
		));
		
		if ( ! in_array($this->field_name, $special))
		{
			$localize = FALSE; // @todo this line is wrong
			
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
}

// END Date_ft class

/* End of file ft.date.php */
/* Location: ./system/expressionengine/fieldtypes/ft.date.php */