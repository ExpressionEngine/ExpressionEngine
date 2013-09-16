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
		parent::__construct($data);
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
	 * Get one or more settings objects for the Template model.
	 *
	 * @param	string	$name	The name of the settings object you wish to
	 * 						retrieve.  Available objects are:
	 *							access: 		Member Group access settings
	 * 							preferences: 	Template Preference settings
	 *
	 * @return	Setting|Setting[]	If a parameter is given then a single Settings
	 * 					object is returned.  If that Settings object is not found
	 * 					NULL is returned.  If no parameter is given an array of
	 * 					Settings objects is returned.  
	 */
	public function getSettings($name=NULL)
	{
		$set = new SettingsSet(array(
    		'preference' => 'PreferenceSettings',
			'access' => 'AccessSettings'
		));
    	
    	if (isset($name))
    	{
        	return $set->getSetting($name);
    	}
    	return $set->getSettings();
	}

	/**
	 * Get the form used to define and edit this Template.
	 *
	 * TODO-MODEL I'm not sure this makes sense.
	 */
	public function getForm()
	{

	}

	/**
	 *
	 */
	public function validate()
	{

	}

	/**
	 *
	 */
	public function save()
	{

	}

	/**
	 *
	 */
	public function delete()
	{

	}
}
