<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class TemplateGroupGateway extends RowDataGateway {

	protected static $_table_name 		= 'template_groups';
	protected static $_primary_key 		= 'group_id';
	protected static $_related_gateways	= array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'    => 'site_id'
		),
		'group_id' => array(
			'Templates' => array(
				'gateway' => 'TemplateGateway',
				'key'    => 'group_id'
			),
			'MemberGroups' => array(
				'gateway' => 'MemberGroupGateway',
				'key' => 'group_id',
				'pivot_table' => 'template_member_groups',
				'pivot_key' => 'template_group_id',
				'pivot_foreign_key' => 'group_id'
			)
		)
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $group_order;
	protected $is_site_default;

}
