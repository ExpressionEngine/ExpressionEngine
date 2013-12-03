<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class TemplateGroupGateway extends RowDataGateway {

	protected static $meta = array(
		'table_name' 		=> 'template_groups',
		'primary_key' 		=> 'group_id',
		'related_gateways' 	=> array(
			'site_id' => array(
				'gateway' => 'SiteGateway',
				'key'    => 'site_id'
			),
			'group_id' => array(
				'gateway' => 'TemplateGateway',
				'key'    => 'group_id'
			)
		)
	);

	public $group_id;
	public $site_id;
	public $group_name;
	public $group_order;
	public $is_site_default;

}
