<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class TemplateGroupEntity extends Entity {
	public $group_id;
	public $site_id;
	public $group_name;
	public $group_order;
	public $is_site_default;


	public static function getTableName()
	{
		return 'exp_template_groups';
	}

	public static funtion getIdName() 
	{
		return 'group_id';
	}

	public static function getRelations()
	{
		return array(
			'site_id' => array(
				'entity' => 'SiteEntity'
				'key' => 'site_id',
			),
			'group_id' => array(
				array(
					'entity' => 'TemplateEntity',
					'key' => 'group_id'
				)
			)
		);
	}

}
