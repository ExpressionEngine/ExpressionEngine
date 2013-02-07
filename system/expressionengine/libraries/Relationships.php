<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Class
 *
 *
 * Takes an array of field_ids that correspond to the ids of the
 * relationship fields that we need to pull entries from in the
 * relationship query. This array comes directly from the tag data.  For
 * example, if we have a channel set up like the following:
 * 
 * Seasons
 * 	title
 * 	url_title
 * 	games		RELATIONSHIP (Games)
 * 	teams		RELATIONSHIP (Teams)
 * 
 * Games
 * 	title
 * 	url_title
 * 	home		RELATIONSHIP (Teams, 1)
 * 	away		RELATIONSHIP (Teams, 1)
 *
 * Teams
 * 	title
 * 	url_title
 * 	players		RELATIONSHIP (Players)
 * 
 * Players
 * 	title
 * 	url_title
 * 	first_name
 * 	last_name
 * 	number
 *
 * Then we might see tag data that looked like the following:
 * 	
 * 	{exp:channel:entries channel="Seasons"}
 * 		{games}
 * 			{games:home:title} vs {games:away:title}
 * 			{games:home:players}
 * 				{games:home:players:number} { games:home:players:first_name} {games:home:players:last_name}
 * 			{/games:home:players}
 * 			{games:away:players}
 * 				{games:away:players:number} {games:away:players:first_name} {games:away:players:last_name}
 * 			{games:away:players}
 * 		{/games}
 * 	{/exp:channel:entires}
 * 
 * 
 * Since the leaf tags also contain the names for each level above them,
 * we only need to pull the leaves out of the single_variables and from
 * that we can generate our array.  In our above example, the leaves would
 * be the following:
 * 
 * {games:home:title}
 * {games:away:title}
 * {games:home:players;number}
 * {games:home:players:first_name}
 * {games:home:players:last_name}
 * {games:away:players:number}
 * {games:away:players:first_name}
 * {games:away:players:last_name}
 * 
 * Each section of those names corresponds to a field and thus a field_id.
 * We can replace the names with field_id and then explode to get arrays:
 * 
 * array(2, 3, 4)
 * array(2, 5, 6)
 * array(2, 3, 7, 8)
 * array(2, 3, 7, 9)
 * array(2, 3, 7, 10)
 * array(2, 5, 7, 8)
 * array(2, 5, 7, 9)
 * array(2, 5, 7, 10)
 * 
 * Since we're only interested in the relationship fields, we can trim off
 * the final field id.  Then we can flip the matrix to we get an array of
 * the ids we need at each level of nesting and run array unique, so we
 * only get unique ids.  We don't need to retain the information about
 * the tree structure, we only need the total list of the needed entries.
 * We still have information about the final tree structure, so we can
 * rebuild the tree from a generated list of entries using Pascal's tree
 * library. So our finally result array passed to build_level will look
 * like this:
 *
 * array(
 * 	array(2),
 *  array(3,5),
 * 	array(7)
 * )
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Relationships {

	private $_table = 'relationships';


 	// --------------------------------------------------------------------

	/**
	 * Get a relationship query object, populated with the information
	 * we'll need to parse out the relationships in this template.
	 *
	 * @param int[] An array of entry ids for the entries we wish to pull the relationships of.
	 * @param int[] The rfields array from the Channel Module at the time of parsing.
	 * @param Template The template we are parsing.
	 * @return Relationship_Query The query object populated with the data queried from the database.
	 */
	public function get_relationship_parser(EE_Template $template, array $relationship_fields)
	{
		echo 'Data in Template passed: <br />';	
		var_dump($template->tag_data);
		var_dump($template->var_single);
		var_dump($template->var_pair);
		return new Relationship_Parser($template, $relationship_fields);
	}

 	// --------------------------------------------------------------------

 	/**
 	 * Clear Cache For Certain Entries
 	 *
 	 * Selectively and intelligently clears the cache for a certain
 	 * entry or entries. This should be the most common use case.
 	 *
 	 * @param	entry_id
 	 *		- entry id or array of ids to clear
 	 *
 	 * @return	void
 	 */
 	public function clear_entry_cache($entry_id)
 	{
 		$db = $this->_isolate_db();

 		if (is_array($entry_id) && count($entry_id))
 		{
 			$db->where_in('rel_parent_id', $entry_id);
 			$db->or_where_in('rel_child_id', $entry_id);
 		}
 		else
 		{
 			$db->where('rel_parent_id', $entry_id);
 			$db->or_where('rel_child_id', $entry_id);
 		}

 		$db->set(array(
 			'rel_data' => '',
 			'reverse_rel_data' => ''
 		));

 		$db->update($this->_table);
 	}

 	// --------------------------------------------------------------------

 	/**
 	 * Clear Cache For Certain Channels
 	 *
 	 * Selectively clears the cache for all entries in a channel or set
 	 * of channels. Useful when changing custom fields.
 	 *
 	 * @param channel_id
 	 *		- channel id or array of ids to clear
 	 *
 	 * @return void
 	 */
 	public function clear_channel_cache($channel_id)
 	{
 		$db = $this->_isolate_db();
 		
 		$db->select('entry_id');

 		if (is_array($channel_id) && count($channel_id))
 		{
 			$db->where_in('channel_id', $channel_id);
 		}
 		else
 		{
 			$db->where('channel_id', $channel_id);
 		}

 		$entry_ids = $db->get('channel_titles')->result_array();

 		// only clear if we actually found any
 		if (count($entry_ids))
 		{
 			$this->clear_entry_cache(
 				array_map('array_pop', $entry_ids) // flattens array of single item arrays
 			);
 		}
 	}

 	// --------------------------------------------------------------------

 	/**
 	 * Clear All Relationship Caches
 	 *
 	 * Be very careful with this method. It can bring sites with a lot
 	 * of relationships to a grinding halt. Be smart about caching!
 	 *
 	 * @access	public
 	 * @return	void
 	 */
 	public function clear_all_caches()
 	{
 		$db = $this->_isolate_db();

 		$db->set(array(
 			'rel_data' => '',
 			'reverse_rel_data' => ''
 		));

 		$db->update($this->_table);
 	}

 	// --------------------------------------------------------------------

 	/**
 	 * Isolate Database
 	 *
 	 * Creates a new blank database object. This way we can do relationship
 	 * management in between other things and not worry about stepping on
 	 * toes on the CI db object.
 	 *
 	 * @return	CI active record object guaranteed to be blank
 	 */
 	public function _isolate_db()
 	{
 		$EE = get_instance();

 		$db = clone $EE->db;

 		$db->_reset_write();
 		$db->_reset_select();

 		return $db;
 	}
}


class Relationship_Parser 
{
	/**
	 * The Template that we are currently parsing Relationships for.
	 */
	protected $template = NULL;

	/**
	 * An array of relationship fields that we have.
	 */	
	protected $relationship_field_ids = array();
	protected $relationship_field_names = array();
	
	/**
	 * Create a relationship parser for the given Template.
	 */
	public function __construct(EE_Template $template, array $relationship_fields)
	{
		$this->template = $template;
		$this->relationship_field_ids = $relationship_fields;
		$this->relationship_field_names = array_flip($relationship_fields);
	}

	/**
	 * TODO handle the case where we find no relationship tags
	 */
	public function query_for_entries(array $entry_ids)
	{

		// Get the array of relationship field ids.  We'll need these when we
		// go to query the database for the related entries.  We'll pull them
		// from the template's single_vars array, which just has a list of
		// single variables.  The returned array contains a single row for each
		// path to a leaf.  So row[0] is id of the relationship field that
		// branches from the root.  row[1] is the id of the relationship field
		// that branches from the node at the second level, and so on.
		$field_ids = $this->_get_needed_relationship_field_ids_from_template();
	
		// Find the shortest branch in our id array.	
		$shortest_branch_length = $this->_find_shortest_branch($field_ids);	
			
		// Perform some transformations on the generated array of field ids to
		// get it into the form we need to perform our query.  That is to say
		// an array of unique field ids grouped by nesting level.
		$field_id_tree = array_map('array_unique', $this->_exchange_array_rows_with_columns($field_ids));

		// Now that we have an array of entry_ids and a tree of field_ids in
		// the format that we need it, as well as the length of the shortest
		// branch in the field_id tree, we're all set to retrieve the list of
		// paths to leaves from the database with all the data we need to build
		// a tree of entries.
		$data = $this->_build_entry_tree(
			$this->_parse_leaves(
				$this->_get_leaves($entry_ids, $field_id_tree, $shortest_branch_length)
			)
		);

		// Now we want to get the entry data for the child entries.
		get_instance()->load->model('channel_entries_model');
		$db = get_instance()->relationships->_isolate_db();

		// Okay, now that we have our relationship data all formatted into a
		// tree, let's going ahead and pull the data for the entries that are
		// in our tree.  We'll just use our nice, concise list of entry ids.
		$entries_result = $db->query(get_instance()->channel_entries_model->get_entry_sql($data['entry_ids']));

		// And then we need to use the lookup table in our data array to
		// populate our mostly empty entries with their data. 
		foreach ($entries_result->result_array() as $entry)
		{
			$data['entry_lookup'][$entry['entry_id']]->set_data($entry);
		}

		echo 'Merged: <pre>'; print_r($data); echo '</pre>';

		$variables = $this->_build_variables_array($data);
	
		var_dump($variables);
	}

	/**
	 * Get the required Relationship Field Ids Out of the Template
	 * 
	 * Use the variables in EE_Template's single_var field to 
	 * determine which relationship_field_ids we'll need to query
	 * against.  Uses the instance of EE_Template passed in the 
	 * constructor.
	 */
	protected function _get_needed_relationship_field_ids_from_template()
	{
		$field_ids = array();

		foreach ($this->template->var_single as $variable)
		{
			if (strpos($variable, ':') !== FALSE)
			{
				// If we have a colon we might have a relationship tag.  If the
				// base of the tag is a relationship field, then we do.
				$parts = explode(':', $variable);
				if (array_key_exists($parts[0], $this->relationship_field_ids))
				{
					$depth_first_ids = array();
					foreach($parts as $field_name) {
						// We only care about the relationship fields.  The other field
						// type names should be the last element in the parts array, and
						// we aren't interested in their ids for the moment.  We'll pick
						// them up in the second query.
						if (isset($this->relationship_field_ids[$field_name]))
						{
							$depth_first_ids[] = $this->relationship_field_ids[$field_name];
						}
					}
					$field_ids[] = $depth_first_ids;
				}
			}
			elseif (array_key_exists($variable, $this->relationship_field_ids))
			{
				$field_ids[] = $this->relationship_field_ids[$variable];
			}
		}

		return $field_ids;
	}

	/**
	 * Find the Shortest Branch in our Relationship Field Id Tree
	 *
	 * Look at our field id matrix (which is actually a matrix where
	 * each row is a path from root to leaf) and find the shortest
	 * path.  We'll need that information when we query the database.
	 *
	 * @param int[]	An array of field ids where each row is a path from root to leaf.
	 * @return int	The length of the shortest path.
	 */	
	protected function _find_shortest_branch(array $field_ids)
	{
		$shortest_branch_length = 10000000000000; // Just an absurdly large number.
		foreach ($field_ids as $leaf)
		{
			if (count($leaf) < $shortest_branch_length)
			{
				$shortest_branch_length = count($leaf);	
			}
		}
		return $shortest_branch_length;
	}

	/**
	 * Perform an exchange of array rows and columns.
	 * 
	 * Takes a 2 dimensional input array and switches the rows
 	 * an columns, performing essentially a matrix rotate.  Returns
	 * the rotated result array.  Should be O(N).
 	 *
	 * @param mixed[]  The target array.
	 * @return mixed[] The input array rotated.
	 */
	protected function _exchange_array_rows_with_columns(array $target)
	{
		$flipped = array();	
		foreach ($target as $row)
		{
			$counter = 0;
			foreach ($row as $item)
			{
				if (!isset($flipped[$counter]))
				{
					$flipped[$counter] = array($item);
				}
				else 
				{
					$flipped[$counter][] = $item;
				}
				$counter++;
			}
		}
		return $flipped;
	}
	
	/**
 	 * Get Paths to Leaves
	 *
	 * Runs a query against the database that will retrieve rows that consist of the
	 * path from the root node to each leaf.  Should only have unique paths.  It pulls out
	 * the data about which field we used to get to each entry node along the path, as well
	 * as the entry_ids.  
	 *  
	 * TODO handle siblings and parents
	 *
	 * @param int[] The array of entry_ids that are the root nodes of our relationship tree.
	 * @param int[]	The tree of field_ids that from the paths between entry nodes in our tree.
	 * @param int	The length of the shortest distance to a leaf in our tree.
	 */
	protected function _get_leaves(array $entry_ids, array $field_ids, $shortest_branch_length)
	{
		$db = get_instance()->relationships->_isolate_db();
	
		$db->distinct();
		$db->select('L0.entry_id as L0_id');
		$db->from('exp_channel_data as L0');

		$level = 0;
		foreach ($field_ids as $ids)
		{
			$branch = '';
			foreach ($ids as $id)
			{
				if ($branch !== '')
				{
					$branch .= ' OR ';	
				}
				$branch .= 'L' . $level . '.field_id_' . $id . ' = L' . $level . 'R.relationship_id';
				if ($level >= $shortest_branch_length)
				{
					$branch .= ' OR L' . $level .'.field_id_' . $id . ' = NULL';
				}
			}
			
			$db->join('exp_zero_wing as L' . $level . 'R', $branch, ($level >= $shortest_branch_length) ? 'left' : '');
			$db->join('exp_channel_data as L' . ($level+1), 
				'L' . ($level+1) . '.entry_id = L' . $level . 'R.entry_id' . (($level+1 > $shortest_branch_length) ? ' OR L' . ($level+1) . '.entry_id = NULL' : ''), 
				($level+1 > $shortest_branch_length) ? 'left' : '');

			// Now add the field ID from this level in.
			foreach ($ids as $id)
			{
				$db->select('L' . $level . '.field_id_' . $id . ' as L' . $level . '_field_id_' . $id);
			} 

			$db->select('L' . $level . 'R.relationship_id as L' . $level .'_relationship_id');
			
			$level++;

			// Add the aliased request for the entry_id to the fields section.		
			$db->select('L' . $level . '.entry_id AS L' . $level . '_id, L' . ($level-1) . '.entry_id as L' . $level . '_parent');

		}
		
		$db->where_in('L0.entry_id', $entry_ids);

		$sql = $db->_compile_select();
		$id_query = $db->query($sql);	
	
		return $id_query->result_array();
	}
	
	/**
	 * Parse Paths to Leaves
	 *
	 * Takes the leaf paths data returned by _get_leaves() and turns it into a form
	 * that's more useable by PHP.  It breaks each row down into arrays with keys
	 * that we can then use to build a tree.
	 *
	 * @param mixed[] The array of leaves with field and entry_ids and the database returned keys.
	 * @return mixed[] An array with the keys parsed.
	 */
	protected function _parse_leaves(array $leaves)
	{
		$parsed_leaves = array();
		foreach ($leaves as $leaf)
		{
			$leaf_result = array();
			foreach ($leaf as $key => $id)
			{
				$level = substr($key, 1, strpos($key, '_')-1);
				$key = substr($key, strpos($key, '_')+1);	
	
				$field_id = NULL;
				$field_name = NULL;
				if (strpos($key, 'field_id') !== FALSE) 
				{
					$field_id = substr($key, strrpos($key, '_')+1);
					$field_name = $this->relationship_field_names[$field_id];
					$key = 'field_id';
				}

				if ( ! isset($leaf_result[$level]))
				{
					$leaf_result[$level] = array();
				}
	
				switch ($key)
				{
					case 'id':
						$leaf_result[$level]['id'] = $id;
					break;
					case 'parent':
						$leaf_result[$level]['parent'] = $id;
					break;
					case 'field_id':
						if( ! isset($leaf_result[$level]['fields']))
						{
							$leaf_result[$level]['fields'] = array();
						}
						$leaf_result[$level]['fields'][$field_name] = $id;
					break;
					case 'relationship_id':
						$leaf_result[$level]['relationship'] = $id;
					break;
				}
			}
			$parsed_leaves[] = $leaf_result;
		}
		return $parsed_leaves;
	}

	/**
	 * Build a Tree of Related Entries
	 *
	 * Alright, take our leaves in reasonable PHP form and build a tree of
	 * Relationship_Entries (unpopulated with the entry data itself) from it.
	 * Initially the tree will be populated only with the entry ids and the
	 * links between them in the tree (one way, from root to children). Returns
	 * an array with three parts: the Tree, a lookup table organized by
	 * Entry_id with references to each Relationship_Entry object in the tree
	 * so we can easily access them (they can appear multiple times), and an
	 * array of entry_ids.
	 *
	 * @param mixed[]	The leaf paths formatted by Relationship_Parser::_parse_leaves().
	 * @return mixed[]	An array with three parts:
	 * 					- tree => the tree of Relationship_Entry objects
	 * 					- entry_lookup => a lookup table organized by id of the Relationship_Entry objects
	 * 					- entry_ids => an array of entry_ids returned, for convience when populating the Relationship_Entry objects
	 */
	protected function _build_entry_tree(array $leaves)
	{
		$entries = array();
		$tree = array();
		foreach ($leaves as $leaf)
		{
			$parent = NULL;
			$field_name = NULL;
			foreach ($leaf as $level)
			{
				if( ! isset($entries[$level['id']]))
				{
					$entries[$level['id']] = new Relationship_Entry($level['id']);
				}
				$entry = $entries[$level['id']];
				
				if ($parent !== NULL && $field_name !== NULL)
				{
					$parent->add_child($field_name, $entry);	
				}
				else if ($parent === NULL && $field_name === NULL)
				{
					// We only want to add it to the tree if we haven't 
					// already added it.
					if( ! isset($tree[$entry->get_entry_id()]))
					{
						$tree[$entry->get_entry_id()] = $entry;
					}
				}
				else
				{
					throw new RuntimeException('Malformed relationship data!');
				}

				// If we don't have a relationship, we're done with this leaf, break out.
				if ( ! isset($level['relationship']) OR  empty($level['relationship']))
				{
					break;	
				}

				$found = FALSE;
				foreach ($level['fields'] as $name => $relationship_id)
				{
					if ($relationship_id == $level['relationship'])
					{
						$found = TRUE;	
						$parent = $entry;
						$field_name = $name;
						
					}
				}

				// This should never, ever happen.  We should never have a case where we've
				// selected an entry as a child who's relationship id does not match any of
				// the parent's relationship fields.
				if( ! $found)
				{
					throw new RuntimeException('Malformed relationship data!');
				}
			}

		}

		$data = array(
			'entry_ids' => array_keys($entries),
			'entry_lookup' => $entries,
			'tree' => $tree
		);

		return $data;
	}	

	/**
	 * Build Variables Array
	 *
 	 * Take the related entries we've retrieved, examine the tag data and then build the array 
	 * of variables that we'll pass to TMPL->parse_variables() for replacing.  * * TODO WRITE ME! 
	 */
	protected function _build_variables_array($data, $field_ids)
	{
		
		echo '_build_variables_array()<br />';
		$EE = get_instance();

		$field_ids = array_flip($field_ids);

		echo 'Data: <br />';
		var_dump($data);
		var_dump($field_ids);

		$variables = array();
	

		return $variables;
	}


 	// --------------------------------------------------------------------
	
	/**
	 * Take the tagdata from a single entry, and the entry's id
	 * and parse any and all relationship variables in the tag data.
	 * We'll need to have already run the query earlier and have the
	 * data we retrieved from it cached.
	 * 
	 * TODO Do I work?
	 */
	public function parse_relationships($entry_id, $tagdata)
	{
					
		$entry_data = $data[$entry_id];

		$variables = array();
		foreach ($EE->TMPL->single_var as $variable)
		{
			$parts = explode(':', $variable);

			$variable_row = &$variables;
			$data_row = &$entry_data;
			foreach ($parts as $part)
			{
				if (is_array($data_row[$part]))
				{
					if ( ! isset($variable_row[$part]))
					{
						$variable_row[$part] = array();
					}

					$variables_row = &$variables[$part];	
					$data_row = &$entry_data[$part];
				}
				else 
				{
					$variables_row[$variable] = $data_row[$part];
				}
			}

		}
		
	}

}

/**
 * An entity wrapper around our entries that can act as a node in a tree.
 * Trying to mess with array references was making my head spin.
 */
class Relationship_Entry
{
	protected $entry_id;
	protected $data;
	protected $children;
		
	public function __construct($entry_id)
	{		
		$this->entry_id = $entry_id;
	}

	public function __get($name)
	{
		if (isset($this->children[$name]))
		{
			return $children[$name];
		}
		elseif (isset($this->entry[$name]))
		{
			return $this->entry[$name];
		}
		return NULL;
	}

	public function get_entry_id()
	{
		return $this->entry_id;
	}

	public function get_data()
	{
		return $this->data;
	}

	public function set_data(array $data)
	{
		$this->data = $data;
		return $this;
	}

	public function get_children()
	{
		return $this->children;
	}

	public function set_children(array $children)
	{
		$this->children = $children;
		return $this;
	}

	public function add_child($field, Relationship_Entry $child)
	{
		if( ! isset($this->children[$field]))
		{
			$this->children[$field] = array();
		}
		
		$this->children[$field][$child->get_entry_id()] = $child;
		return $this;
	}

}

/* End of file Relationships.php */
/* Location: ./system/expressionengine/libraries/Relationships.php */
