<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * Member Custom Fields
 *
 * Stores the defenition of each field
 */
class MemberFieldGateway extends Gateway {

	protected static $meta = array(
		'table_name' => 'member_fields',
		'primary_key' => 'm_field_id'
	);


	// Properties
	public $m_field_id;
	public $m_field_name;
	public $m_field_label;
	public $m_field_description;
	public $m_field_type;
	public $m_field_list_items;
	public $m_field_ta_rows;
	public $m_field_maxl;
	public $m_field_width;
	public $m_field_search;
	public $m_field_required;
	public $m_field_public;
	public $m_field_reg;
	public $m_field_cp_reg;
	public $m_field_fmt;
	public $m_field_order;
}
