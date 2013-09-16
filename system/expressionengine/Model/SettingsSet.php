<?PHP

/**
 * A helper model for managing sets of Settings objects.
 */
class SettingsSet {

	protected $available = array();
	protected $model = NULL;

	/**
	 * Take an array of available settings and an instance of the models to
	 * which the settings belong.
	 *
	 * @param	string[]	$available	An array defining what settings are
	 * 							available to be instantiated by the calling
	 * 							model.  The array is of the structure:
	 * 								array(
	 * 									'name' => 'SettingsClassName',
	 * 									'second_name' => 'SecondSettingsClassName'
	 * 								)
	 * @param	Model		$model	An instance of the model that is doing the 
	 * 							instantiating.
	 */
	public function __construct(array $available, Model $model)
	{
		$this->available = $available;
		$this->model = $model;
	}

	/**
	 * Get a single Setting object by name.
	 *
 	 * @param	string	$name	The name of the object you wish to get.
	 *
	 * @return	Settings	The requested Settings object, or NULL.
	 */
	public function getSetting($name)
	{
		if (isset($this->available[$name]))
		{
			$class = $this->available[$name];
			return new $class($this->model);
		}
		return NULL;
	}

	
	/**
	 * Get all Settings objects for this model.
	 *
	 * @return	Settings[]	An array of settings objects, or an empty array.
	 */	
	public function getSettings() 
	{
		$instantiated = array();
		foreach ($this->available as $class)
		{
			$instantiated[$class] = new $class($this->model);
		}
		return $instantiated;
	}
}
