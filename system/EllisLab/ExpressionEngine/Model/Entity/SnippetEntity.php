<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class SnippetEntity extends Entity {
	
	public static function getTableName()
	{
		return 'exp_snippets';
	}

	public static function getIdName() 
	{
		return 'snippet_id';
	}

	public static function getRelationshipInfo()
	{
		return array(
			'site_id' => array(
				'entity' => 'SiteEntity'
				'key' => 'site_id',
			),
		);
	}

}
