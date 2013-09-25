<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class SiteEntity extends Entity {

	public static function getTableName()
	{
		return 'exp_sites';
	}

	public static function getIdName() 
	{
		return 'site_id';
	}

	public static function getRelationshipInfo()
	{
		return array(
			'site_id' => array(
				array(
					'entity' => 'TemplateEntity',
					'key' => 'site_id',
				),
				array(
					'entity' => 'TemplateGroupEntity',
					'key' => 'site_id'
				),
				array(
					'entity' => 'SnippetEntity',
					'key' => 'site_id'
				)
			),
		);
	}
}
