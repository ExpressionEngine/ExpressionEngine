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
 * ExpressionEngine Channel Parser Component (Custom Member Fields)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_custom_member_field_parser implements EE_Channel_parser_component {

	protected $processed_member_fields = array();

	/**
	 * Check if member fields are enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return in_array('member_data', $disabled) OR empty($pre->channel()->mfields);
	}

	// ------------------------------------------------------------------------

	/**
	 * Reset the processed member tags cache.
	 *
	 * @todo Find all fields like the custom dates?
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return void
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$this->processed_member_fields = array();
		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace all of the custom member data fields.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $pre)
	{
		$mfields = $obj->channel()->mfields;

		$key = $obj->tag();
		$val = $obj->tag_options();

		$data = $obj->row();
		$prefix = $obj->prefix();

		$key = preg_replace('/^'.$prefix.'/', '', $key);

		//  parse custom member fields
		if (isset($mfields[$key]) && array_key_exists('m_field_id_'.$mfields[$key][0], $data))
		{
			if ( ! isset($this->processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$key][0]]))
			{
				$this->processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$key][0]] = ee()->typography->parse_type(
					$data['m_field_id_'.$mfields[$key][0]],
					array(
						'text_format'	=> $mfields[$key][1],
						'html_format'	=> 'safe',
						'auto_links'	=> 'y',
						'allow_img_url' => 'n'
					)
				);
			}

			$tagdata = str_replace(
				LD.$val.RD,
				$this->processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$key][0]],
				$tagdata
			);
		}


		return $tagdata;
	}
}
