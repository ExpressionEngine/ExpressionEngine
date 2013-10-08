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
	 * Find all custom date variables in the template. The return value
	 * will be passed to process for the main parsing loop.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	Found custom date tags.
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$prefix = $pre->prefix();
		$channel = $pre->channel();

		$custom_date_fields = array();

		if (count($channel->dfields) == 0)
		{
			return $custom_date_fields;
		}

		foreach ($channel->dfields as $site_id => $dfields)
		{
  			foreach($dfields as $key => $value)
  			{
  				if ( ! $pre->has_tag($key))
  				{
  					continue;
  				}

  				$key = $prefix.$key;

				if (preg_match_all("/".LD.$key."\s+format=[\"'](.*?)[\"']".RD."/s", $tagdata, $matches))
				{
					for ($j = 0; $j < count($matches[0]); $j++)
					{
						$matches[0][$j] = str_replace(array(LD, RD), '', $matches[0][$j]);

						$custom_date_fields[$matches[0][$j]] = $matches[1][$j];
					}
				}
			}
		}

		return $custom_date_fields;
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
		if ( ! count($custom_date_fields))
		{
			return $tagdata;
		}

		$tag = $obj->tag();
		$data = $obj->row();
		$prefix = $obj->prefix();

		$dfields = $obj->channel()->dfields;

		if (isset($custom_date_fields[$tag]) && isset($dfields[$data['site_id']]))
		{
			foreach ($dfields[$data['site_id']] as $dtag => $dval)
			{
				if (strncmp($tag.' ', $prefix.$dtag.' ', strlen($prefix.$dtag.' ')) !== 0)
				{
					continue;
				}


				if ($data['field_id_'.$dval] == 0 OR $data['field_id_'.$dval] == '')
				{
					$tagdata = str_replace(LD.$tag.RD, '', $tagdata);
					continue;
				}

				// If date is fixed, get timezone to convert timestamp to,
				// otherwise localize it normally
				$localize = TRUE;

				if (isset($data['field_dt_'.$dval]) AND $data['field_dt_'.$dval] != '')
				{
					$localize = $data['field_dt_'.$dval];
				}

				$tagdata = str_replace(
					LD.$tag.RD,
					ee()->localize->format_date(
						$custom_date_fields[$tag],
						$data['field_id_'.$dval],
						$localize
					),
					$tagdata
				);
			}
		}

		return $tagdata;
	}
}