<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Snippet Model
 */
class Snippet_model extends CI_Model {

	/**
	 * Retrieve Snippets from the Database
	 *
	 * Queries the database for Snippets and returns Snippet_Entity objects.
	 * If the array of fields is passed, ANDs the field/value combitations
	 * together and uses them to limit the query.
	 *
	 * @param	mixed[]	$fields	Equality statements to use to limit the query.
	 * 							May be used to get specific Snippet_Entities
	 *							like ``array('site_id'=>1)`` or
	 *							``array('snippet_id'=>20, 'site_id'=>1)``.
	 *
	 * @return	Snippet_Entity[]	An array of populated Snippet_Entity objects.
	 */
	public function fetch(array $fields=array())
	{
		$this->db->select();
		$this->db->from('snippets');

		foreach ($fields as $field=>$value)
		{
			$this->db->where($field, $value);
		}

		return $this->entities_from_db_result($this->db->get());
	}

	/**
	 *
	 */
	public function entities_from_db_result($result)
	{
		$entities = array();
		foreach($result->result_array() as $row)
		{
			$entities[] = new Snippet_Entity($row);
		}
		return $entities;
	}

	/**
	 *
	 */
	public function save(Snippet_Entity $entity)
	{
		$data = $this->_entity_to_db_array($entity);
		if ($entity->snippet_id)
		{
			$this->db->where('site_id', $entity->site_id);
			$this->db->where('snippet_id', $entity->snippet_id);

			$this->db->update('snippets', $data);
			return TRUE;
		}
		else
		{
			$this->db->insert('snippets', $data);
			$entity->snippet = $this->db->insert_id();
			return TRUE;
		}
		throw new RuntimeException('Attempt to save a snippet to the database apparently failed.');
	}

	/**
	 *
	 */
	protected function _entity_to_db_array(Snippet_Entity $entity)
	{
		$data = array(
			'snippet_id' => $entity->snippet_id,
			'site_id' => $entity->site_id,
			'snippet_name' => $entity->snippet_name,
			'snippet_contents' => $entity->snippet_contents
		);
		return $data;
	}
}


/**
 * Snippet Database Entity
 *
 * An entity object to handle database interactions for Snippets.
 */
class Snippet_Entity {

	private $snippet_id;
	private $site_id;
	private $snippet_name;
	private $snippet_contents;

	/**
	 * Construct a Snippet Entity, Optionally from a DB result row
	 *
	 * Constructs a Snippet Entity.  If a database result row is passed in
	 * associative array form, it is used to initialize the entity.
	 *
	 * @param mixed[]	Optional.  A single DB_result associative array.
	 * 					As retrieved from DB_result::result_array()
	 */
	public function __construct(array $snippets_row = array())
	{
		foreach ($snippets_row as $property=>$value)
		{
			if ( property_exists($this, $property))
			{
				$this->{$property} = $value;
			}
		}
	}

	/**
	 * Magic Getter for Property Access
	 *
	 * Only allows access to properties that exist.
	 */
	public function __get($name)
	{
		if ( strpos('_', strval($name)) === 0  OR ! property_exists($this, $name))
		{
			throw new RuntimeException('Attempt to access non-existent property "' . $name . '"');
		}

		return $this->{$name};
	}

	/**
	 * Magic Setter for Property Access
	 *
	 * Only allows properties that exist to be set.
	 */
	public function __set($name, $value)
	{
		if ( strpos('_', strval($name)) === 0 OR ! property_exists($this, $name))
		{
			throw new RuntimeException('Attempt to access non-existent property "' . $name . '"');
		}

		$this->{$name} = $value;
	}

}

// EOF
