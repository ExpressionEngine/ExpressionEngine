<?PHP
namespace EllisLab\ExpressionEngine\Model\Entity;

/**
 *
 */
abstract class Entity {
	protected static $meta = array();

	private $dirty = array();

	public function __construct(array $data = array())
	{
		foreach ($data as $property => $value)
		{
			if (property_exists($this, $property))
			{
				$this->{$property} = $value;
			}
		}
	}

	public static function getMetaData($key=NULL)
	{
		if (empty(static::$meta))
		{
			throw new \UnderflowException('No meta data set for this entity!');
		}

		if ( ! isset($key))
		{
			return static::$meta;
		}

		return static::$meta[$key];
	}

	/**
	 *
	 */
	public function save()
	{
		// Nothing to save!
		if (empty($this->dirty))
		{
			return;
		}

		$save_array = array();
		foreach ($this->dirty as $property)
		{
			$save_array[$property] = $this->{$property};
		}

		$id_name = static::getMetaData('primary_key');
		if (isset($this->{$id_name}))
		{
			ee()->db->where($id_name, $this->{$id_name});
			ee()->db->update(static::getMetaData('table_name'), $save_array);
		}
		else
		{
			ee()->db->insert(static::getMetaData('table_name'), $save_array);
		}
	}

	/**
	 *
	 */
	public function delete()
	{
		$primary_key = static::getMetaData('primary_key');
		if (! isset($this->{$primary_key}))
		{
			throw new ModelException('Attempt to delete an Entity with out an attached ID!');
		}
		ee()->db->delete(static::getMetaData('table_name'), array($primary_key, $this->{$primary_key}));
	}

}
