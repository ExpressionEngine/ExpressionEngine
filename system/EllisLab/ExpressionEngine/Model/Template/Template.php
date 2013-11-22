<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model as Model;

/**
 *
 */
class Template extends Model {

	protected static $meta = array(
		'primary_key'	=> 'template_id',
		'entity_names'	=> array('TemplateEntity'),
		'key_map'		=> array(
			'template_id' => 'TemplateEntity',
			'group_id'    => 'TemplateEntity',
			'last_author_id' => 'TemplateEntity'
		)
	);

	/**
	 *
	 */
	public function getTemplateGroup()
	{
		return $this->manyToOne('TemplateGroup', 'TemplateGroup', 'group_id', 'group_id');
	}

	public function setTemplateGroup(TemplateGroup $template_group)
	{
		$this->setRelated('TemplateGroup', $template_group);
		$this->group_id = $template_group->group_id;
		return $this;
	}

	public function getLastAuthor()
	{
		return $this->manyToOne('LastAuthor', 'Member', 'last_author_id', 'member_id');
	}

	public function setLastAuthor(Member $member)
	{
		$this->setRelated('LastAuthor', $member);	
		$this->last_author_id = $member->member_id;
		return $this;
	}

}

