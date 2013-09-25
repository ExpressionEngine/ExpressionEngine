<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class StatusEntity extends Entity {
	public $status_id;
	public $group_id;
	public $site_id;
	public $status;
	public $status_order;
	public $highlight;

	public static function getTableName()
	{
		return 'exp_statuses';
	}

	public static function getIdName() 
	{
		return 'status_id';
	}

	public static function getRelationshipInfo()
	{
		return array(
			'site_id' => array(
				'entity' => 'SiteEntity'
				'key' => 'site_id',
			),
			'group_id' => array(
				'entity' => 'StatusGroupEntity',
				'key' => 'group_id'
			)
		);
	}


}
