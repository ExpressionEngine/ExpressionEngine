<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class RelationshipEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'relationships',
		'primary_key' => 'relationship_id',
		'related_entities' => array(
			'parent_id' => array(
				'entity' => 'ChannelEntryEntity',
				'key' => 'entry_id'
			),
			'child_id' => array(
				'entity' => 'ChannelEntryEntity',
				'key' => 'child_id'
			),
			'field_id' => array(
				'entity' => 'ChannelFieldEntity',
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
