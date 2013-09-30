<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Errors as Errors;

/**
 *
 */
class Template extends Model {

	protected static $meta = array(
		'primary_key'	=> 'template_id',
		'entity_names'	=> array('TemplateEntity'),
		'key_map'		=> array(
			'template_id' => 'TemplateEntity',
			'group_id'    => 'TemplateEntity'
		)
	);

	/**
	 *
	 */
	public function getTemplateGroup()
	{
		return $this->manyToOne('TemplateGroup', 'group_id');
	}

	/**
	 *
	 */
	public function validate()
	{
		return new Errors();
	}

}

