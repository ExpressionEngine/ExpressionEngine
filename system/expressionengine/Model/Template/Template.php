<?PHP
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model as Model;


/**
 *
 */
class Template extends Model {
	protected static $entity_name = 'TemplateEntity';

	protected static $relationship_info = array(
		'TemplateGroup' => array(
			'entity' => 'TemplateEntity',
			'type' => 'one',
			'property' => 'template_group',
			'key' => 'group_id'
		),
	);	

	/**
	 *
	 */
	public function getId()
	{
		return $this->entity->template_id;
	}
	
	/**
	 *
	 */
	public function validate()
	{
		return new Errors();
	}

}

