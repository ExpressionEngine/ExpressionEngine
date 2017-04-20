<?php

namespace EllisLab\Addons\FluidBlock\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Attachment Model for the Forum
 *
 * A model representing an attachment in the Forum.
 *
 * @package		ExpressionEngine
 * @subpackage	Forum Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
		'ChannelFields' => array(
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

}

// EOF
