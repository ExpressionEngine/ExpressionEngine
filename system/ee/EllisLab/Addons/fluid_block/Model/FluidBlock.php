<?php

namespace EllisLab\Addons\FluidBlock\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Model\Content\FieldData;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

/**
 * ExpressionEngine Fluid Block Model
 */
class FluidBlock extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'fluid_block_data';

	protected static $_typed_columns = array(
		'block_id'      => 'int',
		'entry_id'      => 'int',
		'field_id'      => 'int',
		'field_data_id' => 'int',
		'order'         => 'int',
	);

	protected static $_relationships = array(
		'ChannelEntry' => array(
			'type' => 'belongsTo',
			'model' => 'ee:ChannelEntry',
			'weak' => TRUE,
			'inverse' => array(
				'name' => 'FluidBlock',
				'type' => 'hasMany',
				'weak' => TRUE
			)
		),
		'ChannelField' => array(
			'type' => 'belongsTo',
			'model' => 'ee:ChannelField',
			'weak' => TRUE,
			'inverse' => array(
				'name' => 'FluidBlock',
				'type' => 'hasMany',
				'weak' => TRUE
			)
		),
		'BlockField' => array(
			'type' => 'belongsTo',
			'from_key' => 'block_id',
			'to_key'   => 'field_id',
			'model' => 'ee:ChannelField',
			'weak' => TRUE,
			'inverse' => array(
				'name' => 'FluidBlock',
				'type' => 'hasOne',
				'weak' => TRUE
			)
		)
	);

	protected $id;
	protected $block_id;
	protected $entry_id;
	protected $field_id;
	protected $field_data_id;
	protected $order;

	public function getFieldData()
	{
		$cache_key = "ChannelField/{$this->field_id}/Data/{$this->field_data_id}";

		if (($field_data = ee()->session->cache(__CLASS__, $cache_key, FALSE)) === FALSE)
		{
			$field_data = ee('Model')->make('FieldData')->forField($this->ChannelField);

			ee()->db->where('id', $this->field_data_id);
			$rows = ee()->db->get('channel_data_field_' . $this->field_id)->result_array();

			if ( ! empty($rows))
			{
				$field_data->set($rows[0]);
			}

			ee()->session->set_cache(__CLASS__, $cache_key, $field_data);
		}

		return $field_data;
	}

	public function getField(FieldData $field_data = NULL)
	{
		$field = $this->ChannelField->getField();
		$field->setContentId($this->entry_id);

		$field_data = ($field_data) ?: $this->getFieldData();

		$field->setData($field_data->getProperty('field_id_' . $this->field_id));

		if ($field_data->getProperty('field_ft_' . $this->field_id) !== NULL)
		{
			$format = $field_data->getProperty('field_ft_' . $this->field_id);

			// Need to set this property because it will override the
			// format on successive calls to `getField()`
			$this->ChannelField->field_fmt = $format;
			$field->setFormat($format);
		}

		if ($field_data->getProperty('field_dt_' . $this->field_id) !== NULL)
		{
			$format = $field_data->getProperty('field_dt_' . $this->field_id);
			$field->setTimezone($format);
		}

		return $field;
	}
}

// EOF
