<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component (Custom Date Fields)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_custom_date_parser implements EE_Channel_parser_component {

	/**
	 * Check if custom dates are enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->dfields);
	}

	// ------------------------------------------------------------------------

	/**
	 * Fulfilling the requirements of the abstract class we inherit from.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	An empty array.
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return array();
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the custom date fields.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $custom_date_fields)
	{
		$data = $obj->row();
		$dfields = $obj->channel()->dfields;

		$dates = array();
		$gmt_dates = array();

		foreach ($dfields[$data['site_id']] as $dtag => $dval)
		{
			if ($data['field_id_'.$dval] == 0 OR $data['field_id_'.$dval] == '')
			{
				$dates[$dtag] = '';
				continue;
			}

			// If date is fixed, get timezone to convert timestamp to,
			// otherwise localize it normally
			$localize = TRUE;

			if (isset($data['field_dt_'.$dval]) AND $data['field_dt_'.$dval] != '')
			{
				$localize = $data['field_dt_'.$dval];
			}

			if ($localize)
			{
				$dates[$dtag] = $data['field_id_'.$dval];
			}
			else
			{
				$gmt_dates[$dtag] = $data['field_id_'.$dval];
			}

		}

		if ( ! empty($dates))
		{
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, $dates, TRUE);
		}

		if ( ! empty($gmt_dates))
		{
			$tagdata = ee()->TMPL->parse_date_variables($tagdata, $gmt_dates, FALSE);
		}

		return $tagdata;
	}
}