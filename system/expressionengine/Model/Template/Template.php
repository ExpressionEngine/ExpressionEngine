<?PHP
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model as Model;

/**
 *
 */
class Template extends Model {
	
	public static function getEntityNames()
	{
		return array('TemplateEntity');
	}

	public static function getKeyMap()
	{
		return array(
			'template_id' => 'TemplateEntity',
			'group_id' => 'TemplateEntity'
		);
	}

	public static function getPrimaryKeyName()
	{
		return 'template_id';
	}

	/**
	 *
	 */
	public function getTemplateGroup()
	{
		return $this->manyToOne('TemplateGroup', 'group_id', 'group_id');
	}
	
	/**
	 *
	 */
	public function validate()
	{
		return new Errors();
	}

}

