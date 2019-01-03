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
 * Channel Parser Component (Relationships)
 */
class EE_Channel_relationship_parser implements EE_Channel_parser_component {

	/**
	 * Check if relationships are enabled.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return empty($pre->channel()->rfields) OR in_array('relationships', $disabled);
	}

	/**
	 * Set up the relationship parser's tree and data pre-caching.
	 *
	 * The returned object will be passed to replace() as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return Array	The relationship parser object
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		if (empty($pre->channel()->rfields))
		{
			return NULL;
		}

		ee()->load->library('relationships_parser');
		ee()->load->model('grid_model');

		try
		{
			$relationships = $pre->channel()->rfields;
			foreach ($pre->channel()->gfields as $site_id => $fields)
			{
				foreach ($fields as $field_name => $field_id)
				{
					$prefix = $field_name.':';

					$columns = ee()->grid_model->get_columns_for_field($field_id, 'channel');

					foreach ($columns as $col)
					{
						if ($col['col_type'] == 'relationship')
						{
							$relationships[$site_id][$prefix.$col['col_name']] = $col['col_id'];
						}
					}
				}
			}

			return ee()->relationships_parser->create(
				$relationships,
				$pre->entry_ids()
			);
		}
		catch (EE_Relationship_exception $e)
		{
			ee()->TMPL->log_item($e->getMessage());
		}

		return NULL;
	}

	/**
	 * Replace all of the relationship fields in one fell swoop.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $relationship_parser)
	{
		if ( ! isset($relationship_parser))
		{
			return $tagdata;
		}

		$row = $obj->row();
		$channel = $obj->channel();

		try
		{
			return $relationship_parser->parse($row['entry_id'], $tagdata, $channel);
		}
		catch (EE_Relationship_exception $e)
		{
			return $tagdata;
		}
	}
}

// EOF
