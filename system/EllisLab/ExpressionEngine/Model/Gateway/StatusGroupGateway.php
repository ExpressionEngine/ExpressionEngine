<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class StatusGroupGateway extends RowDataGateway {
	protected static $_table_name = 'status_groups';
	protected static $_primary_key = 'group_id';
	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		),
		'group_id' => array(
			'gateway' => 'StatusGateway',
			'key' => 'group_id'
		)
	);


	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_name;

}
