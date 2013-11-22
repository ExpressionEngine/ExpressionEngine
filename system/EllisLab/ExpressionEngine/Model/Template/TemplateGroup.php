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
		return $this->oneToMany('Templates', 'Template', 'group_id', 'group_id');
	}

	public function setTemplates(array $templates)
	{
		$this->setRelated('Templates', $templates);
		foreach($templates as $template)
		{
			$template->group_id = $this->group_id;
		}
		return $this;
	}

	public function getMemberGroups()
	{
		return $this->manyToMany('MemberGroup', 'template_group_id', 'group_id', 'MemberGroups');
	}

	public function setMemberGroups(array $member_groups)
	{
		$this->setRelated('MemberGroups', $member_groups);
		return $this;
	}
}
