<?PHP

abstract class Entity {
	public $table_name = NULL;
	public $id_name = NULL;

	public $dirty = array();
	

	/**
	 *
	 */
	public function save()
	{
		$save_array = array();
		foreach($this->dirty as $property)
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

}
