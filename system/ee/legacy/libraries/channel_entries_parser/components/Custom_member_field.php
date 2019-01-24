<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Parser Component (Custom Member Fields)
 */
class EE_Channel_custom_member_field_parser implements EE_Channel_parser_component {

	protected $member_field_models = array();

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
		$this->member_field_models = array();

		$member_field_ids = array();
		foreach ($pre->channel()->mfields as $field_name => $attrs)
		{
			$member_field_ids[] = $attrs[0];
		}

		if ( ! empty($member_field_ids))
		{
			$this->member_field_models = ee('Model')->get('MemberField', array_unique($member_field_ids))
				->all()
				->indexBy('field_id');
		}

		return NULL;
	}

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

		$field = ee('Variables/Parser')->parseVariableProperties($key);

		if ( ! isset($mfields[$field['field_name']]))
		{
			return $tagdata;
		}

		$field_id = $mfields[$field['field_name']][0];

		//  parse custom member fields
		if (array_key_exists('m_field_id_'.$field_id, $data)
			&& isset($this->member_field_models[$field_id]))
		{
			$member_field = $this->member_field_models[$field_id];

			$tagdata = $member_field->parse(
				$data['m_field_id_'.$field_id],
				$data['member_id'],
				'member',
				$field,
				$tagdata,
				array(
					'channel_html_formatting' => 'safe',
					'channel_auto_link_urls' => 'y',
					'channel_allow_img_urls' => 'n',
          'field_ft_'.$field_id => $data['m_field_ft_'.$field_id]
				),
				$key
			);
		}
		return $tagdata;
	}
}

// EOF
