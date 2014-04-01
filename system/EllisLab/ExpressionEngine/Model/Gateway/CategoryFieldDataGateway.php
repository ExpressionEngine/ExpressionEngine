<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\Gateway;

class CategoryFieldDataGateway extends FieldDataGateway {
	protected static $_table_name = 'category_field_data';
	protected static $_primary_key = 'cat_id';
	protected static $_field_table = 'category_fields';
	protected static $_field_id_name = 'field_id';
	protected static $_related_gateways = array(
		'cat_id' => array(
			'gateway' => 'CategoryGateway',
			'key'	 => 'cat_id'
		),
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'group_id' => array(
			'gateway' => 'CategoryGroupGateway',
			'key'	 => 'group_id'
		),
	);

	// Properties
	public $cat_id;
	public $site_id;
	public $group_id;

}
