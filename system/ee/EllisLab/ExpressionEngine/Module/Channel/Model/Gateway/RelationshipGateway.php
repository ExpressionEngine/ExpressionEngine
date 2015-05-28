<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class RelationshipGateway extends Gateway {
	protected static $meta = array(
		'table_name' => 'relationships',
		'primary_key' => 'relationship_id',
		'related_gateways' => array(
			'parent_id' => array(
				'gateway' => 'ChannelEntryGateway',
				'key' => 'entry_id'
			),
			'child_id' => array(
				'gateway' => 'ChannelEntryGateway',
				'key' => 'child_id'
			),
			'field_id' => array(
				'gateway' => 'ChannelFieldGateway',
				'key' => 'field_id'
			)
		)
	);

	// Properties
	public $relationship_id;
	public $parent_id;
	public $child_id;
	public $field_id;
	public $grid_field_id;
	public $grid_col_id;
	public $grid_row_id;
	public $order;
}
