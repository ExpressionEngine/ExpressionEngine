<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model as Model;

class TemplateGroup extends Model {
	protected static $meta = array(
		'entity_names' => array('TemplateGroupEntity'),
		'key_map' => array(
			'group_id' => 'TemplateGroupEntity'
		),
		'primary_key' => 'group_id'
	);

	/**
	 *
	 */
	public function getTemplates()
	{
		return $this->oneToMany('Template', 'group_id', 'group_id');
	}
	
	/**
	 *
	 */
	public function validate()
	{
		return new Errors();
	}



}
