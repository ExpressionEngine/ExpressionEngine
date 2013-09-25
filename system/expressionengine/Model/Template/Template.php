<?PHP


/**
 *
 */
class Template extends Model {
	protected static $entity_name = 'TemplateEntity';

	/**
	 *
	 */
	public function getId()
	{
		return $this->entity->template_id;
	}

	/**
	 * Retrieve the Model of the group this Template belongs to.
	 *
	 * @return	TemplateGroup	The template group that this Template belongs 
	 * 							to.
	 */
	public function getTemplateGroup() 
	{
	
	}
	
	/**
	 *
	 */
	public function validate()
	{
		return new Errors();
	}

}

