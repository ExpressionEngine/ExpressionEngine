<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model as Model;

class TemplateGroup extends Model {

	protected static $entity_name = 'TemplateGroupEntity';
	
	protected static $relations = array(
		'Template' => array(
			'entity' => 'TemplateGroupEntity',
			'type' => 'many',
			'property' => 'templates',
			'key' => 'group_id'
		)
	);


}
