<?PHP
namespace EllisLab\ExpressionEngine\Model\Entity;

/**
 *
 */
abstract class Entity {
	protected static $table_name = NULL;
	protected static $id_name = NULL;
	protected static $relations = array();

	public $dirty = array();
	

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

	/**
	 *
	 */
	public function save()
	{
		$save_array = array();
		foreach ($this->dirty as $property)
		{
			$save_array[$property] = $this->{$property};
		}

		if (isset($this->{$this->id_name}))
		{
			ee()->db->where($this->id_name, $this->{$this->id_name});
			ee()->db->update($this->table_name, $save_array);
		}	
		else
		{
			ee()->db->insert($this->table_name, $save_array);
		}
	}

	/**
	 *
	 */
	public function delete()
	{
		if (! isset($this->{$this->id_name}))
		{
			throw new ModelException('Attempt to delete an Entity with out an attached ID!');
		}
		ee()->db->delete($this->table_name, array($this->id_name, $this->{$this->id_name}));
	}

	/**
	 *
	 */
	public static function getTableName()
	{
		return $this->table_name;
	}

	/**
	 *
	 */
	public static function getIdName()
	{
		return $this->id_name;
	}

	/**
	 *
	 */
	public static function getRelations()
	{
		return $this->relations;
	}

}
