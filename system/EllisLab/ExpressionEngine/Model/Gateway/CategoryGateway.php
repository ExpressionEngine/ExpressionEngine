<?php
namespace Ellislab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class CategoryGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'categories',
		'primary_key' => 'cat_id',
		'related_gateways' => array(
			'cat_id' => array(
				'gateway' => 'ChannelTitleGateway',
				'pivot_table' => 'category_posts',
				'pivot_key' => 'cat_id',
				'pivot_foreign_key' => 'entry_id'
			),
			
			'site_id' => array(
				'gateway' => 'SiteGateway',
				'key'	 => 'site_id'
			),
			'group_id' => array(
				'gateway' => 'CategoryGroupGateway',
				'key'	 => 'group_id'
			),
			'parent_id' => array(
				'gateway' => 'CategoryGateway',
				'key'	 => 'cat_id'
			),
		)
	);

	// Properties
	public $cat_id;
	public $site_id;
	public $group_id;
	public $parent_id;
	public $cat_name;
	public $cat_url_title;
	public $cat_description;
	public $cat_image;
	public $cat_order;
}
