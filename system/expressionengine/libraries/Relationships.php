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
	 * Parse Relationship Tags
	 *
	 * Parse out any relationship tags from a channel entries tag.  We'll
	 * find our relationship tags in the channel field data, parse them
	 * into a form we can query against and then replace the data in question.
	 * 
	 * TODO handle the case where we find no relationship tags
	 */
	public function get_relationship_data(array $entry_ids, array $relationship_fields)
	{
		$EE = get_instance();

		$field_ids = array();

		var_dump($EE->TMPL->var_single);
		foreach ($EE->TMPL->var_single as $variable)
		{
			if (strpos($variable, ':') !== FALSE)
			{
				// If we have a colon we might have a relationship tag.  If the
				// base of the tag is a relationship field, then we do.
				$parts = explode(':', $variable);
				if (array_key_exists($parts[0], $relationship_fields))
				{
					$depth_first_ids = array();
					foreach($parts as $field_name) {
						// We only care about the relationship fields.  The other field
						// type names should be the last element in the parts array, and
						// we aren't interested in their ids for the moment.  We'll pick
						// them up in the second query.
						if (isset($relationship_fields[$field_name]))
						{
							$depth_first_ids[] = $relationship_fields[$field_name];
						}
					}
					$field_ids[] = $depth_first_ids;
				}
			}
			elseif (array_key_exists($variable, $relationship_fields))
			{
				$field_ids[] = $relationship_fields[$variable];
			}
		}
		var_dump($field_ids);

		
		$shortest_distance_to_leaf = 10000000000000; // Just an absurdly large number.
		foreach ($field_ids as $leaf)
		{
			if (count($leaf) < $shortest_distance_to_leaf)
			{
				$shortest_distance_to_leaf = count($leaf);	
			}
		}
	
			
		// Perform some transformations on the generated array of field ids to
		// get it into the form we need to perform our query.  That is to say
		// an array of unique field ids grouped by nesting level.
		$field_ids = array_map('array_unique', $this->_exchange_array_rows_with_columns($field_ids));

		var_dump($field_ids);

		var_dump($shortest_distance_to_leaf);
		// Okay, now that we have the base parent entry ids and the 
		// ids of the relationship fields we'll need to be querying, in the right
		// format, go ahead and get the ids of all child entries.
		$data = $this->get_child_entry_ids($entry_ids, $field_ids, $shortest_distance_to_leaf);

		// Now we want to get the entry data for the child entries.
		$EE->load->model('channel_entries_model');
		$db = $this->_isolate_db();

		// We needed to hang on to the parent->child relationships in the returned entry data,
		// the straight list of entry ids can be retrieved from the keys in data.  We'll just
		// run array_keys() and send it to get_entry_sql()
		$entries_result = $db->query($EE->channel_entries_model->get_entry_sql(array_keys($data)));

		// Now we need to attach the resulting entry arrays to
		// the right spot in our data array.  The data array
		// is keyed to entry_id, so it's pretty easy.
		foreach ($entries_result->result_array() as $entry)
		{
			$data[$entry['entry_id']]['entry'] = $entry;
		}

		var_dump($data);

		/*	
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
		 * Given this, what does parse_variables need to see?
		 * 
		 *array( 'games'=>array(
		 *  These are games...
		 *	0=>array(
		 *		0=>array(
		 *			'games:home:title' => '',
		 *			'games:away:title' => '',
	 	 *			'games:home:players' => array(
	 	 *				These are players...
	 	 *				0=>array(
		 * 					0=>array(
		 *						'games:home:players:number' => '',
		 *						'games:home:players:first_name' => '',
	 	 *						'games:home:players:last_name' => ''
		 *					),
		 *					1=>array(
		 *						'games:home:players:number' => '',
		 *						'games:home:players:first_name' => '',
	 	 *						'games:home:players:last_name' => ''
		 *					)
		 *				)
	 	 *			),
	 	 *			'games:away:players' => array(
		 *				These are players...
	 	 *				0=>array(
		 * 					0=>array(
		 *						'games:away:players:number' => '',
		 *						'games:away:players:first_name' => '',
	 	 *						'games:away:players:last_name' => ''
		 *					),
		 *					1=>array(
		 *						'games:away:players:number' => '',
		 *						'games:away:players:first_name' => '',
	 	 *						'games:away:players:last_name' => ''
		 *					)
		 *				)
	 	 *			),
		 * 		)
		 * 	),
		 *  1=>array(
		 *		0=>array(
		 *			'games:home:title' => '',
		 *			'games:away:title' => '',
	 	 *			'games:home:players' => array(
	 	 *				These are players...
	 	 *				0=>array(
		 * 					0=>array(
		 *						'games:home:players:number' => '',
		 *						'games:home:players:first_name' => '',
	 	 *						'games:home:players:last_name' => ''
		 *					),
		 *					1=>array(
		 *						'games:home:players:number' => '',
		 *						'games:home:players:first_name' => '',
	 	 *						'games:home:players:last_name' => ''
		 *					)
		 *				)
	 	 *			),
	 	 *			'games:away:players' => array(
		 *				These are players...
	 	 *				0=>array(
		 * 					0=>array(
		 *						'games:away:players:number' => '',
		 *						'games:away:players:first_name' => '',
	 	 *						'games:away:players:last_name' => ''
		 *					),
		 *					1=>array(
		 *						'games:away:players:number' => '',
		 *						'games:away:players:first_name' => '',
	 	 *						'games:away:players:last_name' => ''
		 *					)
		 *				)
	 	 *			),
		 * 		)
		 *	)
		 *)
 	 	 */

		$variables = $this->_build_variables_array($data);
	
		var_dump($variables);
	}

	/**
	 * Build Variables Array
	 *
 	 * Take the related entries we've retrieved, examine the tag data and then build the array 
	 * of variables that we'll pass to TMPL->parse_variables() for replacing.
	 * 
	 */
	protected function _build_variables_array($data)
	{
		echo '_build_variables_array()<br />';
		$EE = get_instance();

		$variables = array();
	
		var_dump($EE->TMPL->var_single);
		var_dump($EE->TMPL->var_pair);
		var_dump($EE->TMPL->tagdata);

		return $variables;
	}

	/**
	 * Perform an exchange of array rows and columns.
	 * 
	 * Takes a 2 dimensional input array and switches the rows
 	 * an columns, performing essentially a matrix rotate.  Returns
	 * the rotated result array.  Should be O(N).
 	 * 
	 * @return array The input array rotated.
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
	 * Retrieve the entry data for all related entries required by a call
	 * of the channel entries tag.
 	 * 
	 * TODO handle siblings and parents
	 * 
	 *  FIXME This is ugly as all hell.  Probably possible to put this into the active record class. 
	 * didn't feel like doing that on the first pass.  Just wanted to make it work.
	 */
	protected function _get_child_entry_ids(array $entry_ids, array $field_ids, $shortest_branch_length)
	{
		echo 'Before the query: <br />';
		var_dump($field_ids);
		$db = $this->_isolate_db();
	
		$db->distinct();
		$db->select('L0.entry_id as L0');
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
			$db->select('L' . $level . '.entry_id AS L' . $level . ', L' . ($level-1) . '.entry_id as L' . $level . '_parent');

		}
		
		$db->where_in('L0.entry_id', $entry_ids);

		$sql = $db->_compile_select();
		echo '<code>' . $sql . '</code>';
		
		$id_query = $db->query($sql);	

		var_dump($id_query->result_array());
	
		$children = $this->_collapse_array_distinct($id_query->result_array());
		echo '<br />Result: <br />';
		var_dump($children);
	
		return $children;
	}

	/**
	 * Collapse a 2d Array and Check for Value Uniqueness
	 * 
	 * Takes a 2 dimensional array and collapses it down to a single
	 * dimension.  It performs a check for value uniqueness while it
	 * is collapsing.  The result is a single dimensional array containing
	 * all unique values from the input array. Should be O(N).
	 * 
	 * @return array A single dimensional array containing all unique values input.
	 */
	protected function _collapse_array_distinct(array $array)
	{
		$result = array();
		foreach ($array as $row)
		{
			for ($i=0, $level = 'L' . $i; isset($row[$level]); $i++, $level = 'L' . $i)
			{	
				$id = $row[$level];
				$result[$id] = array('id'=>$row[$level], 'parent_id'=>(isset($row[($level . '_parent')]) ? $row[($level . '_parent')] : 0));
			}
		}
		return $result;
	}	

 	// --------------------------------------------------------------------
	
	/**
 	 * Assume we get the data in the following format:
	 * 
	 * 	array(
	 * 		entry_id => array(
	 * 			field_name => array(
	 *				entry_id => array(
	 *					field_name => array(
	 *						entry_id => array(),
	 *						entry_id => aray()
	 *					),
	 *					field_name => array(
	 *						entry_id => array(),
	 *						entry_id => array()
	 * 					)
	 * 				)
	 * 			)
	 *		)
	 * 	)
	 *	
	 * 		
	 */
	public function parse_relationships($entry_id, array $data, $tagdata)
	{
					
		// Parse_variables expects data of the form
		//
		// array(
	 	//  # one loop cycle for outer most pair
	 	//  # -- in our case, just one, the entry for the passed tagdata
	 	// 	n => array(
		// 		# single variable
		// 		name => value,
		// 		name => array(
		// 			# variable pair
		//			0 => array(
		//				# single values
		//				name => value,
		//				name => value,
		//				name => array(
		//					0 => array(
		//						name => value
		//					)
		//				)	
		//			)
		// 		)
		// 	)
		//		
		// So for our test case:
		//
		// 	<h1><a href="{path="relationships/season"}/winter-2013">Winter 2013</a></h1>
		// 	<p>This season will have the following teams:</p>
		// 	<div class="teams">
		// 	{teams}
		// 		<h2>{teams:title}</h2>
		// 		<div class="team">
		// 		{teams:players}
		// 			<span class="player">{teams:players:number} {teams:players:first_name} {teams:players:last_name}</span>
		// 		{/teams:players}
		// 		</div>
		// 	{/teams}
		// 	</div>
		// 	<p>Who will play each other in the following games:</p>
		// 	<div class="games">
		// 	{games}
		// 		<div class="game">
		// 		{games:home:title} ({games:home_score}) vs {games:away:title} ({games:away_score})
		// 		{games:home:players}
		// 			{games:home:players:number} {games:home:players:first_name} {games:home:players:last_name}
		// 		{/games:home:players}
		// 		</div>
		// 	{/games}
		// 	</div>
		//
		// Assume we have...
		// 
		// array(
		//	entry_id (winter 2013) => array(
		//		teams => array(
		//			entry_id (Omnislash) => array(
		//				title => Omnislash,
		//				players => array(
		//					entry_id (Jason Yoder) => array(
		//						number => 1,
		//						first_name => Jason,
		//						last_name => Yoder
		//					)
		//					entry_id (Adam Schobinger) => array(
		//						etc...
		//					)
		//				)
		//			)
		//		),
		//		games => array(
		//		)
		//	)
		//) 
		// 	 
		// 
		// We need...
		//
		// array(
		//  # Winter 2013
		// 	0 => array(
		//		teams => array(
		//			0 => array(
		//				0 => array(
		//					teams:title => Omnislash,
		//					teams:players => array(
		//						0 => array(
		//							0 => array(
		//								teams:players;number => 1,
		//								teams:players:first_name => Jason,
		//								teams:players:last_name => Yoder 
		//							),
		//							1 => array(
		//								teams:players:number => 2,
		//								teams;players:first_name => Adam,
		//								teams:players:last_name => Schobinger
		//							)
		//						)
		//					)
		//				)
		//				1 => array(
		//					teams:title => Hammertime,
		//					teams:players => array(
		//						0 => array(
		//							0 => array(
		//								teams:players:number => 3,
		//								teams:players:first_name => Dane,
		//								teams:players:last_name => Lockhart,
		//		
		//		
		$entry_data = $data[$entry_id];
		
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
 	private function _isolate_db()
 	{
 		$EE = get_instance();

 		$db = clone $EE->db;

 		$db->_reset_write();
 		$db->_reset_select();

 		return $db;
 	}
}

/* End of file Relationships.php */
/* Location: ./system/expressionengine/libraries/Relationships.php */
