<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class StatusGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'statuses',
		'primary_key' => 'status_id',
		'related_gateways' => array(
			'group_id' => array(
				'gateway' => 'StatusGroupGateway',
				'key' => 'group_id'
			),
			'site_id' => array(
				'gateway' => 'SiteGateway',
				'key' => 'site_id'
			)
		)
	);


	public $status_id;
	public $group_id;
	public $site_id;
	public $status;
	public $status_order;
	public $highlight;
}
