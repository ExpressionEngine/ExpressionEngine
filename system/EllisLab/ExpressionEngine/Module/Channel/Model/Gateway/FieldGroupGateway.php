<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class FieldGroupGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'field_groups',
		'primary_key' => 'group_id', 
		'related_gateways' => array(
			'site_id' => array(
				'gateway' => 'SiteGateway',
				'key'	 => 'site_id'
			)
		)	
	);

	// Properties
	public $group_id;
	public $site_id;
	public $group_name;
}
