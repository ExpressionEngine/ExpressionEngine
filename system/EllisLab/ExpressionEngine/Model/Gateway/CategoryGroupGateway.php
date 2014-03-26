<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class CategoryGroupGateway extends RowDataGateway {
	protected static $_table_name = 'category_groups';
	protected static $_primary_key = 'group_id';
	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'group_id' => array(
			'gateway' => 'CategoryGateway',
			'key' => 'group_id'
		)
	);


	// Properties
	public $group_id;
	public $site_id;
	public $group_name;
	public $sort_order;
	public $exclude_group;
	public $field_html_formatting;
	public $can_edit_categories;
	public $can_delete_categories;
}
