<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Error\Errors as Errors;

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
		return $this->manyToOne('TemplateGroup', 'group_id');
	}

	public function setTemplateGroup(TemplateGroup $template_group)
	{
		$this->setRelated('TemplateGroup', $template_group);
	}

	public function getLastAuthor()
	{
		return $this->manyToOne(
			'Member', 
			'last_author_id', 
			'member_id', 
			'LastAuthor'
		);
	}

	public function setLastAuthor(Member $member)
	{
		$this->setRelated('LastAuthor', $member);	
	}

}

