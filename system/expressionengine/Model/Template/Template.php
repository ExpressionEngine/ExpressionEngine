<?PHP

/**
 *
 */
class Template extends Model {
	
	/**
	 * 
	 */
	public function __construct(array $data=array())
	{
		parent::__construct('TemplateEntity', $data);
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
