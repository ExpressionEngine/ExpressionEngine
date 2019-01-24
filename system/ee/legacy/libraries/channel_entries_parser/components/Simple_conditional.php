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
 * Channel Parser Component (Simple Conditionals)
 */
class EE_Channel_simple_conditional_parser implements EE_Channel_parser_component {

	/**
	 * @todo Fast check for simple conditionals?
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return FALSE;
	}

	/**
	 * No preprocessing required.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return void
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return NULL;
	}

	/**
	 * Replace all of the simple conditionals
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $pre)
	{
		$tag = $obj->tag();
		$tag_options = $obj->tag_options();
		$data = $obj->row();
		$prefix = $obj->prefix();

		// @todo
		$key = $tag;
		$val = $tag_options;

		$cfields = $obj->channel()->cfields;

		if (strpos($key, '|') !== FALSE && is_array($val))
		{
			foreach($val as $item)
			{
				// Basic fields

				if (isset($data[$item]) AND $data[$item] != "")
				{
					$tagdata = str_replace(LD.$prefix.$key.RD, $data[$item], $tagdata);
					continue;
				}

				// Custom channel fields

				if ( isset( $this->cfields[$data['site_id']][$item] ) AND isset( $data['field_id_'.$cfields[$data['site_id']][$item]] ) AND $data['field_id_'.$cfields[$data['site_id']][$item]] != "")
				{
					$entry = ee()->typography->parse_type(
						$data['field_id_'.$cfields[$data['site_id']][$item]],
						array(
								'text_format'	=> $data['field_ft_'.$cfields[$data['site_id']][$item]],
								'html_format'	=> $data['channel_html_formatting'],
								'auto_links'	=> $data['channel_auto_link_urls'],
								'allow_img_url' => $data['channel_allow_img_urls']
							)
					);

					$tagdata = str_replace(LD.$prefix.$key.RD, $entry, $tagdata);

					continue;
				}
			}

			// Garbage collection
			$val = '';
			$tagdata = str_replace(LD.$prefix.$key.RD, "", $tagdata);
		}

		return $tagdata;
	}
}

// EOF
