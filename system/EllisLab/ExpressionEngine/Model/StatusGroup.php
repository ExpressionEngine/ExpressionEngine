<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model;

class StatusGroup extends Model {
	protected static $_meta = array(
		'primary_key' => 'group_id',
		'gateways' => 'StatusGroupGateway',
		'key_map' => array(
			'group_id' => 'StatusGroupGateway',
			'site_id' => 'StatusGroupGateway'
		)
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
}
