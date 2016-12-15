<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.5.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine OptionFieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class OptionFieldtype extends EE_Fieldtype {

	public function display_field($data)
	{
		return NULL;
	}

	/**
	 * Creates a mini Grid field based on the data in the 'value_label_pairs' key
	 *
	 * @return MiniGridInput object
	 */
	protected function getValueLabelMiniGrid($data)
	{
		$grid = ee('CP/MiniGridInput', array(
			'field_name' => 'value_label_pairs'
		));
		$grid->loadAssets();
		$grid->setColumns(array(
			'Value',
			'Label'
		));
		// TODO: lang key
		$grid->setNoResultsText('No <b>key/value pairs</b> found.', 'Add');
		$grid->setBlankRow(array(
			array('html' => form_input('value', '')),
			array('html' => form_input('label', ''))
		));
		$grid->setData(array());

		if (isset($data['value_label_pairs']))
		{
			if (isset($data['value_label_pairs']['rows']))
			{
				$data['value_label_pairs'] = $data['value_label_pairs']['rows'];
			}

			$pairs = array();
			$i = 1;
			foreach ($data['value_label_pairs'] as $value => $label)
			{
				$pairs[] = array(
					'attrs' => array('row_id' => $i),
					'columns' => array(
						array('html' => form_input('value', $value)),
						array('html' => form_input('label', $label))
					)
				);
				$i++;
			}

			$grid->setData($pairs);
		}

		return $grid;
	}

	/**
	 * Saves settings for a field that allows its options to be specified in
	 * a mini Grid field
	 *
	 * @return Settings to be returned from save_settings()
	 */
	public function save_settings($data)
	{
		if ($data['field_pre_populate'] == 'v')
		{
			$pairs = array();

			if (isset($data['value_label_pairs']['rows']))
			{
				$data['value_label_pairs'] = $data['value_label_pairs']['rows'];
			}

			foreach ($data['value_label_pairs'] as $row)
			{
				$pairs[$row['value']] = $row['label'];
			}

			if ($this->content_type() == 'grid')
			{
				return array(
					'field_pre_populate' => $data['field_pre_populate'],
					'field_list_items' => '',
					'value_label_pairs' => $pairs
				);
			}

			return array(
				'value_label_pairs' => $pairs
			);
		}
		else
		{
			if ($this->content_type() == 'grid')
			{
				return array(
					'field_pre_populate' => $data['field_pre_populate'],
					'field_list_items' => $data['field_list_items'],
					'value_label_pairs' => array()
				);
			}

			return array();
		}
	}
}

// EOF
