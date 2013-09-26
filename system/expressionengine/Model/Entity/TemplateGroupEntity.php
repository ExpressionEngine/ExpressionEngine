<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class TemplateGroupEntity extends Entity {

	protected static $meta = array(
		'table_name' 		=> 'template_groups',
		'primary_key' 		=> 'group_id',
		'related_entities' 	=> array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'    => 'site_id'
			),
			'group_id' => array(
				'entity' => 'TemplateEntity',
				'key'    => 'group_id'
			)
		)
	);

	public $group_id;
	public $site_id;
	public $group_name;
	public $group_order;
	public $is_site_default;

}
