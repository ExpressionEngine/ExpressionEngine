<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model as Model;

class TemplateGroup extends Model {

	protected static $meta = array(
		'primary_key'	=> 'group_id',
		'entity_names'	=> array('TemplateGroupEntity'),
		'key_map'		=> array(
			'group_id' => 'TemplateGroupEntity'
		),
		'cascade' => 'Templates'
	);

	/**
	 *
	 */
	public function getTemplates()
	{
		return $this->oneToMany('Template', 'group_id', 'group_id');
	}

	public function getMemberGroups()
	{
		return $this->manyToMany('MemberGroup', 'template_group_id', 'group_id', 'MemberGroups');
	}

	/**
	 *
	 */
	public function validate()
	{
		return new Errors();
	}
}
