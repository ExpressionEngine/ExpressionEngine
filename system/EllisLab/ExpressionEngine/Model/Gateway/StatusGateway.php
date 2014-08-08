<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class StatusGateway extends RowDataGateway {
	protected static $_table_name = 'statuses';
	protected static $_primary_key = 'status_id';
	protected static $_related_gateways = array(
		'group_id' => array(
			'gateway' => 'StatusGroupGateway',
			'key' => 'group_id'
		),
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		)
	);


	protected $status_id;
	protected $group_id;
	protected $site_id;
	protected $status;
	protected $status_order;
	protected $highlight;
}
