<?PHP
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model as Model;

/**
 *
 */
class Template extends Model {
	protected static $entity_name = 'TemplateEntity';

	/**
	 *
	 */
	public function getTemplateGroup()
	{
		return $this->belongsTo('TemplateGroup', 'TemplateEntity', 'group_id');
	}

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

