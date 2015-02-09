<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MemberField extends Model {

	protected static $_primary_key = 'm_field_id';
	protected static $_gateway_names = array('MemberFieldGateway');

	// Properties
	protected $m_field_id;
	protected $m_field_name;
	protected $m_field_label;
	protected $m_field_description;
	protected $m_field_type;
	protected $m_field_list_items;
	protected $m_field_ta_rows;
	protected $m_field_maxl;
	protected $m_field_width;
	protected $m_field_search;
	protected $m_field_required;
	protected $m_field_public;
	protected $m_field_reg;
	protected $m_field_cp_reg;
	protected $m_field_fmt;
	protected $m_field_order;

}
