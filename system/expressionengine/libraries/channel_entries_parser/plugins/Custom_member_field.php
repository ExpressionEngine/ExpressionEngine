<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Plugin (Custom Member Fields)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_custom_member_field_parser implements EE_Channel_parser_plugin {

	protected $processed_member_fields = array();

	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->mfields);
	}

	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		$this->processed_member_fields = array();
		return NULL;
	}

	public function replace($tagdata, EE_Channel_data_parser $obj, $pre)
	{
		$mfields = $obj->channel()->mfields;

		$key = $obj->tag();
		$val = $obj->tag_options();

		$data = $obj->row();
		$prefix = $obj->prefix();

		$key = str_replace($prefix, '', $key);

		//  parse custom member fields
		if (isset($mfields[$key]) && array_key_exists('m_field_id_'.$mfields[$key][0], $data))
		{
			if ( ! isset($this->processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$key][0]]))
			{
				$this->processed_member_fields[$data['member_id']]['m_field_id_'.$mfields[$key][0]] = get_instance()->typography->parse_type(
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
