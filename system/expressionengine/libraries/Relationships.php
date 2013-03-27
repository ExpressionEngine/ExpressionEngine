<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'libraries/datastructures/Tree.php');

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
 * relationship query. This array comes directly from the tag data. For
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
 * We're only interested in the relationship fields, but we want to maintain
 * the parent child relationships of the tags, so we turn it into a tree
 * hierachy such as this:
 *
 *					{games}
 *		{games:home}		{games:away}
 * {games:home:players}	{games:away:players}
 * 
 * 
 * Using the branch depth of that structure, we can construct a join that will
 * return all of the potential entry ids from our adjecency list table. More
 * importantly, we can overlay this parent sibling information on our tree so
 * that we end up with a set of ids for each parent at any given tag:
 * 
 * 				 {[6, 7]}
 * 				/		\	
 *  {6:[2,4], 7:[8,9]}    	{6:[], 7:[2,5]}
 * 		/					\
 * 	...				  		 ...
 * 
 * 
 * This also means that we can query for most of the required data before the
 * channel entries loop runs, thus vastly reducing the number of queries you
 * would get from nesting channel entry tags or sitting on a spanish beach.
 *
 *
 * There are a few edge cases that we need to consider in this approach.
 * Since an entry can have multiple parents, some of which may not be on
 * the current tree, we cannot rely on the tree to provide us with parent
 * information. Instead we add a query with an inverted tree at those edge-
 * case locations.
 * 
 * 
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
	 * Get a relationship parser and query object, populated with the information
	 * we'll need to parse out the relationships in this template.
	 *
	 * @param int[] An array of entry ids for the entries we wish to pull the relationships of.
	 * @param int[] The rfields array from the Channel Module at the time of parsing.
	 * @param Template The template we are parsing.
	 * @return Relationship_Query The query object populated with the data queried from the database.
	 */
	public function get_relationship_parser(EE_Template $template, array $relationship_fields, array $custom_fields)
	{
		return new Relationship_Parser($template, $relationship_fields, $custom_fields);
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

/**
 *
 */
class Relationship_Parser 
{
	protected $template = NULL;							// The Template that we are currently parsing Relationships for

	protected $custom_fields = array();					// Custom field id to name mapping
	protected $relationship_field_ids = array();		// Relationship field map (name => field_id)
	protected $relationship_field_names = array();		// Another relationship field map (field_id => name)

	protected $variables = array();						// A cache of the tree and lookup table for the main per entry parsing step
	
	/**
	 * Create a relationship parser for the given Template.
	 */
	public function __construct(EE_Template $template, array $relationship_fields, array $custom_fields)
	{
		$this->template = $template;
		$this->custom_fields = $custom_fields;
		$this->relationship_field_ids = $relationship_fields;
		$this->relationship_field_names = array_flip($relationship_fields);
	}

	/**
	 * Check if a given tag name is a relationship field and if
	 * so return its id.
	 */
	protected function _get_relationship_field_id($tag_name)
	{
		if ( ! $tag_name)
		{
			return FALSE;
		}

		// last segment
		$tag_name = ':'.$tag_name;
		$tag_name = substr(strrchr($tag_name, ':'), 1);

		if (array_key_exists($tag_name, $this->relationship_field_ids))
		{
			return $this->relationship_field_ids[$tag_name];
		}

		if ($tag_name == 'siblings' ||
			$tag_name == 'parents')
		{
			return $tag_name;
		}

		return FALSE;
	}

	/**
	 * Find All Relationships of the Given Entries in the Template 
	 *
	 * Searches the template the parser was constructed with for relationship
	 * tags and then builds a tree of all the requested related entries for
	 * each of the entries passed in the array.
	 *
	 * @param	int[]	An array of entry ids who's relations we need
	 *					to find.
	 */
	public function query_for_entries(array $entry_ids)
	{
		// first, we need a tree
		$root = $this->_build_tree($entry_ids);

		if ($root === NULL)
		{
			return array();
		}

		// For space savings and subtree querying each node is pushed
		// its own set of entry ids per parent ids:
		//
		//						 {[6, 7]}
		//						/		\	
		//		 {6:[2,4], 7:[8,9]}    	{6:[], 7:[2,5]}
		//				/					\
		//	  		...				  		 ...

		// By pushing them down like this the subtree query is very simple.
		// And when we parse we simply go through all of them and make that
		// many copies of the node's tagdata.

		$root_leave_paths = $this->_subtree_query($root, $entry_ids);
		$unique_ids = $this->_unique_entry_ids($root, $root_leave_paths);

		$all_ids = array_merge($entry_ids, $unique_ids);


		// not strictly necessary, but keeps all the id loops parent => children
		// it has no side-effects since all we really care about for the root
		// node are the children.
		foreach ($entry_ids as $id)
		{
			$root->add_entry_id($id, $id);
		}



		// Ok, so now we need to repeat that process iteratively for
		// all of the special snowflakes on our tree. This is fun!

		$query_node_iter = new RecursiveIteratorIterator(
			new QueryNodeIterator(array($root)),
			RecursiveIteratorIterator::SELF_FIRST
		);


		foreach ($query_node_iter as $node)
		{
			$depth = $query_node_iter->getDepth();

			if ($depth == 0 && $node->is_root())
			{
				continue;
			}

			// Get all of the parent node's ids, these will be the where_in
			// for our query. They are taken from the direct parent of the
			// node, *not* the closure parent of this iterator. That wouldn't
			// make sense.
			$ids = call_user_func_array('array_merge', $node->parent()->entry_ids);

			// If it's a parent tag, we reverse the query, which flips that
			// segment of the tree so that to the parser the parents simply
			// look like children of the name "parents". Savvy?
			if ($node->field_name() == 'parents')
			{
				$result_ids = $this->_parent_tree_query($node, $ids);
			}
			elseif ($node->field_name() == 'siblings')
			{
				// @todo @pk top level siblings and their querynode > * counterparts
				// need to push down parent ids if parent exists?
				$ids = array_keys($node->parent()->entry_ids);
				$result_ids = $this->_sibling_query($node, $ids);
			}

			$result_ids = $this->_unique_entry_ids($node, $result_ids);

			// Store flattened ids for the big entry query
			$all_ids = array_merge($all_ids, $result_ids);
		}


		// ready set, main query.
		$EE = get_instance();
		$db = $EE->relationships->_isolate_db();

		$EE->load->model('channel_entries_model');

		$sql = $EE->channel_entries_model->get_entry_sql(array_unique($all_ids));
		$entries_result = $db->query($sql);


		// Build an id => data map for quick retrieval during parsing
		$entry_lookup = array();

		foreach ($entries_result->result_array() as $entry)
		{
			$entry_lookup[$entry['entry_id']] = $entry;
		}


		// READY TO PARSE! FINALLY!
		$this->variables = array(
			'tree' => $root,
			'lookup' => $entry_lookup
		);
	}


	protected function _build_tree(array $entry_ids)
	{
		// we build our tree straight from the tag hierarchy
		$str = $this->template->tagdata;

		// No variables?  No reason to continue...
		if (strpos($str, '{') === FALSE OR ! preg_match_all("/".LD."([^{]+?)".RD."/", $str, $matches))
		{
			return NULL;
		}

		// Match up tag pairs, record their hierarchy, and create a node
		// instance for each tag.

		$reversed = array_reverse($matches[0]);
		unset($matches);

		$uuid = 0;
		$nodes = array();
		$id_stack = array();
		$tag_stack = array();

		foreach ($reversed as $tag)
		{
			$tag_name = substr($tag, 1, strcspn($tag, ' }', 1));

			$is_closing = ($tag_name[0] == '/');
			$tag_name = ltrim($tag_name, '/');

			$field_id = $this->_get_relationship_field_id($tag_name);

			if ( ! $field_id)
			{
				continue;
			}

			$uuid++;
			$parent_id = end($id_stack);

			if ($is_closing)
			{
				$id_stack[] = $uuid;
				$tag_stack[] = $tag_name;
			}
			elseif ($tag_name == end($tag_stack))
			{
				array_pop($tag_stack);
				$lookup_id = array_pop($id_stack);

				$params = get_instance()->functions->assign_parameters($tag);

				$node = $nodes[$lookup_id]['node'];
				$node->params = $params ? $params : array();

				$node->open_tag = $tag;

				continue;
			}

			$node_class = 'ParseNode';

			// @todo @pk a little more complicated than this with parameters?
			if (preg_match('/.*parents$/', $tag_name) || $tag_name == 'siblings')
			{
				$node_class = 'QueryNode';
			}

			$node = new $node_class($tag_name, array(
				'field_id'	=> $field_id,
				'tag_info'	=> array(),
				'entry_ids'	=> array()
			));

			$nodes[$uuid] = array(
				'node'		  => $node,
				'parent_uuid' => $parent_id
			);
		}

		// Doing our own parsing let's us do error checking
		if (count($tag_stack))
		{
			// going backwards has the unfortunate side effect that we end up
			// finding missmatched closing tags. Should be ok though - either
			// way you'll be in the template looking for pairs.
			throw new RuntimeException('Unmatched Closing Tag: "{/'.end($tag_stack).'}"');
		}

		// Now that we have node instances and their hierarchy, we
		// can connect them into a tree shape
		$root = new QueryNode('__root__');

		foreach ($nodes as $data)
		{
			$node = $data['node'];
			$parent_id = $data['parent_uuid'];

			if (isset($nodes[$parent_id]))
			{
				$parent = $nodes[$parent_id]['node'];
				$parent->add($node);
			}
			else
			{
				$root->add($node);
			}
		}

		return $root;
	}

	protected function _unique_entry_ids($root, $leave_paths)
	{
		$it = new RecursiveIteratorIterator(
			new ParseNodeIterator(array($root)),
			RecursiveIteratorIterator::SELF_FIRST
		);

		// add entry ids to the proper tree parse nodes
		// L0 = root
		// L1 = closure-depth=1 querynodes (match field names)
		// ...

		$root_offset = 0;

		$all_ids = array();
		$leaves = $this->_parse_leaves($leave_paths);

		foreach ($it as $node)
		{
			$depth = $it->getDepth();

			if ($depth == 0 && $node->is_root())
			{
				$root_offset = -1;
				continue;
			}

			// the lookup below starts one up from the root
			$depth += $root_offset;
			$field_id = $node->field_id;

			if ($field_id == 'parents')
			{
				$field_name = $node->param('field');

				if ( ! $field_name)
				{
					throw new RuntimeException('Parent tag without field parameter.');
				}

				$field_id = $this->relationship_field_ids[$field_name];
			}

			// @todo disgustingly unclear conditional
			// the first check is if sibling is part of the tag, the second
			// if sibling is the *entire* tag.
			if ($field_id != 'siblings' OR $node->name() == 'siblings')
			{
				if ($node->name() == 'siblings')
				{
					$field_name = $node->param('field');

					if ( ! $field_name)
					{
						throw new RuntimeException('Sibling tag without field parameter.');
					}

					$field_id = $this->relationship_field_ids[$field_name];
				}

				if (isset($leaves[$depth][$field_id]))
				{
					foreach ($leaves[$depth][$field_id] as $parent => $children)
					{
						foreach ($children as $child)
						{
							$all_ids[] = $child['id'];
							$node->add_entry_id($parent, $child['id']);
						}
					}
				}
			}
			else
			{
				$siblings = array();
				$possible_siblings = $node->parent()->entry_ids;

				foreach ($possible_siblings as $parent => $children)
				{
					$children = array_unique($children);

					// find all sibling permutations by rotating the array
					for ($i = 0; $i < count($children); $i++)
					{
						$key = array_shift($children);
						$node->add_entry_id($key, $children);
						array_push($children, $key);
					}
				}
			}
		}

		return $all_ids;
	}


	protected function _sibling_query($root, $entry_ids)
	{
		$depths = $this->_min_max_branches($root);

		$longest_branch_length = $depths['longest'];
		$shortest_branch_length = $depths['shortest'];

		$db = get_instance()->relationships->_isolate_db();

		$db->distinct();
		$db->select('L0.field_id as L0_field');
		$db->select('S.child_id AS L0_parent');	// the parent is the joined on child id from our entry_ids
		$db->select('L0.child_id as L0_id');
		$db->from('exp_zero_wing as L0');

		for ($level = 0; $level <= $longest_branch_length; $level++)
		{
			$db->join('exp_zero_wing as L' . ($level+1), 
				'L' . ($level) . '.child_id = L' . ($level+1) . '.parent_id' . (($level+1 >= $shortest_branch_length) ? ' OR L' . ($level+1) . '.parent_id = NULL' : ''), 
				($level+1 >= $shortest_branch_length) ? 'left' : '');

			if ($level > 0)
			{
				// Now add the field ID from this level in. We've already done level 0,
				// so just skip it.
				$db->select('L' . $level . '.field_id as L' . $level . '_field');
				$db->select('L' . $level . '.parent_id AS L' . $level . '_parent');
				$db->select('L' . $level . '.child_id as L' . $level . '_id');
			}
		}

		$db->join('exp_zero_wing as S', 'L0.parent_id = S.parent_id');
		$db->where_in('S.child_id', $entry_ids);

		return $db->get()->result_array();
	}

	protected function _parent_tree_query($root, $entry_ids)
	{
		// tree branch length extrema
		$depths = $this->_min_max_branches($root);

		$shortest_branch_length = $depths['shortest'];
		$longest_branch_length = $depths['longest'];

		$db = get_instance()->relationships->_isolate_db();

		$db->distinct();
		$db->select('L0.field_id as L0_field');
		$db->select('L0.child_id AS L0_parent'); // switched to make the tree building algorithm easier
		$db->select('L0.parent_id as L0_id');
		$db->from('exp_zero_wing as L0');


		for ($level = 0; $level <= $longest_branch_length; $level++)
		{
			if ($level == 0)
			{
				$db->join('exp_zero_wing as L' . ($level+1), 
					'L' . ($level) . '.parent_id = L' . ($level+1) . '.parent_id' . (($level+1 >= $shortest_branch_length) ? ' OR L' . ($level+1) . '.child_id = NULL' : ''), 
					($level+1 >= $shortest_branch_length) ? 'left' : '');
			}
			else
			{
				$db->join('exp_zero_wing as L' . ($level+1), 
					'L' . ($level) . '.child_id = L' . ($level+1) . '.parent_id' . (($level+1 >= $shortest_branch_length) ? ' OR L' . ($level+1) . '.parent_id = NULL' : ''), 
					($level+1 >= $shortest_branch_length) ? 'left' : '');

				// Now add the field ID from this level in. We've already done level 0,
				// so just skip it.
				$db->select('L' . $level . '.field_id as L' . $level . '_field');
				$db->select('L' . $level . '.parent_id AS L' . $level . '_parent');
				$db->select('L' . $level . '.child_id as L' . $level . '_id');
			}
		}

		$db->where_in('L0.child_id', $entry_ids);

		return $db->get()->result_array();
	}

	protected function _subtree_query($root, $entry_ids)
	{
		// tree branch length extrema
		$depths = $this->_min_max_branches($root);

		$longest_branch_length = $depths['longest'];
		$shortest_branch_length = $depths['shortest'];

		$db = get_instance()->relationships->_isolate_db();

		$db->distinct();
		$db->select('L0.field_id as L0_field');
		$db->select('L0.parent_id AS L0_parent');
		$db->select('L0.child_id as L0_id');
		$db->from('exp_zero_wing as L0');

		for ($level = 0; $level <= $longest_branch_length; $level++)
		{
			$db->join('exp_zero_wing as L' . ($level+1), 
				'L' . ($level) . '.child_id = L' . ($level+1) . '.parent_id' . (($level+1 >= $shortest_branch_length) ? ' OR L' . ($level+1) . '.parent_id = NULL' : ''), 
				($level+1 >= $shortest_branch_length) ? 'left' : '');

			if ($level > 0)
			{
				// Now add the field ID from this level in. We've already done level 0,
				// so just skip it.
				$db->select('L' . $level . '.field_id as L' . $level . '_field');
				$db->select('L' . $level . '.parent_id AS L' . $level . '_parent');
				$db->select('L' . $level . '.child_id as L' . $level . '_id');
			}
		}

		$db->where_in('L0.parent_id', $entry_ids);

		return $db->get()->result_array();
	}


	protected function _min_max_branches($tree)
	{
		$it = new RecursiveIteratorIterator(
			new ParseNodeIterator(array($tree)),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		$shortest = 1E10;
		$longest = 0;

		foreach ($it as $leaf)
		{
			$depth = $it->getDepth();

			if ($tree->is_root())
			{
				$depth -= 1;
			}

			if ($depth < $shortest)
			{
				$shortest = $depth;
			}

			if ($depth > $longest)
			{
				$longest = $depth;
			}
		}

		if ($shortest > 1E9)
		{
			$shortest = 0;
		}

		return compact('shortest', 'longest');
	}
	
	/**
	 * Parse Paths to Leaves
	 *
	 * Takes the leaf paths data returned by _get_leaves() and turns it into a form
	 * that's more useable by PHP. It breaks each row down into arrays with keys
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
			$i = 0;

			while (isset($leaf['L'.$i.'_field']))
			{
				$field_id = $leaf['L'.$i.'_field'];
				$entry_id = $leaf['L'.$i.'_id'];
				$parent_id = $leaf['L'.$i.'_parent'];

				if ($entry_id == NULL)
				{
					break;
				}

				$field_name = $this->relationship_field_names[$field_id];

				if ( ! isset($parsed_leaves[$i]))
				{
					$parsed_leaves[$i] = array();
				}

				if ( ! isset($parsed_leaves[$i][$field_id]))
				{
					$parsed_leaves[$i][$field_id] = array();
				}

				if ( ! isset($parsed_leaves[$i][$field_id][$parent_id]))
				{
					$parsed_leaves[$i][$field_id][$parent_id] = array();
				}

				$parsed_leaves[$i++][$field_id][$parent_id][] = array(
					'id' => $entry_id,
					'field' => $field_name,
					'parent' => $parent_id
				);
			}
		}

		return $parsed_leaves;
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Take the tagdata from a single entry, and the entry's id
	 * and parse any and all relationship variables in the tag data.
	 * We'll need to have already run the query earlier and have the
	 * data we retrieved from it cached.
	 *
	 * @param	int		The id of the entry we're working with.
	 * @param	string	The tagdata to replace relationship tags in.
	 * 						With all normal entry tags already parsed.
	 *
	 * @return 	string	The parsed tagdata, with all relationship tags
	 *						replaced.
	 */
	public function parse_relationships($entry_id, $tagdata, $channel)
	{
		// If we have no relationships, then we can quietly bail out.
		if ($this->variables == NULL)
		{
			return $tagdata;
		}

		get_instance()->load->library('api');
		get_instance()->api->instantiate('channel_fields');

		$tree = $this->variables['tree'];
		$lookup = $this->variables['lookup'];

		get_instance()->session->set_cache('relationships', 'channel', $channel);

		$tagdata = $tree->parse($entry_id, $tagdata, $lookup);

		return $tagdata;
	}
}




class ParseNode extends EE_TreeNode {

	protected $entries;
	protected $entries_lookup;

	private $childTags;				// namespaced tags underneath it that are not relationship tags, constructed from tagdata

	
	/**
	 * Retrieve the field name
	 *
	 * Removes the namespace so that we're only left with the last
	 * segment. If you need the full tag name, use `name()`
	 *
	 * @return 	string	The raw field name
	 */
	public function field_name()
	{
		$field_name = ':'.$this->name;
		return substr($field_name, strrpos($field_name, ':') + 1);
	}

	/**
	 * Set a parameter
	 *
	 * Params is theoretically accessible through __get, but we don't
	 * return a reference so we can't modify it (except overriding it).
	 *
	 * @param 	string	The parameter name
	 * @param	mixed	The parameter value
	 * @return 	void
	 */
	public function set_param($key, $value = NULL)
	{
		$this->data['params'][$key] = $value;
	}

	/**
	 * Get a parameter
	 *
	 * Accessor to avoid constantly doing isset checks.
	 *
	 * @param 	string	The parameter name
	 * @param	mixed	a default to return if the parameter is not set
	 * @return 	mixed	parameter value || default
	 */
	public function param($key, $default = NULL)
	{
		return isset($this->data['params'][$key]) ? $this->data['params'][$key] : $default;
	}

	/**
	 * Make the node aware of a relationship
	 *
	 * Creates an internal parent->child relationship so that we can return
	 * all child ids for any given incoming parent later.
	 *
	 * @param 	int		the parent entry id
	 * @param	int		the child entry id
	 * @return 	void
	 */
	public function add_entry_id($parent, $child)
	{
		$ids =& $this->data['entry_ids'];

		if ( ! isset($ids[$parent]))
		{
			$ids[$parent] = array();
		}

		if (is_array($child))
		{
			$ids[$parent] = array_merge($ids[$parent], $child);
		}
		else
		{
			$ids[$parent][] = $child;
		}
	}

	/**
	 * Parse the tag's internal data (and all of its children)
	 *
	 * @todo Work in progress!
	 *
	 * @param 	int		the entry id
	 * @param	string	the tag enclosed template string
	 * @param	mixed	channel entries lookup {id => [data]}
	 * @return 	string	parsed tagdata
	 */
	public function parse($id, $tagdata, array $entries_lookup)
	{
		if ( ! isset($this->entry_ids[$id]))
		{
			return $tagdata;
		}

		if ($this->is_root())
		{
			foreach ($this->children() as $child)
			{
				$tagdata = $child->parse($id, $tagdata, $entries_lookup);
			}

			return $tagdata;
		}

		$this->entries_lookup = $entries_lookup;
		$this->entries = array_unique($this->entry_ids[$id]);
		$tag = preg_quote($this->name, '/');

		return preg_replace_callback('/{'.$tag.'[^}:]*}(.+?){\/'.$tag.'}/is', array($this, '_replace'), $tagdata);
	}

	/**
	 * preg_replace callback for parse()
	 *
	 * Does the actual data replacement, Handles all of the things you
	 * would expect from EE such as conditionals, backspace, count
	 * date formatting, etc.
	 *
	 * @todo Work in progress!
	 *
	 * @param 	mixed	regex matched template chunk
	 * @return 	string	parsed chunk
	 */
	protected function _replace($matches)
	{
		$entries = $this->entries;
		$entries_lookup = $this->entries_lookup;

		$children = $this->children();

		$tagdata = $matches[1];
		$params = $this->params;

		$result = '';
		$name = $this->name;
		$prefix = $name.':';

		$channel = get_instance()->session->cache('relationships', 'channel');
		
		// @todo date formatting
		$count = 0;
		$total_results = count($entries);

		// reorder the ids
		if ($this->param('orderby'))
		{
			$order_by = explode('|', $this->param('orderby'));
			$sort = explode('|', $this->param('sort', 'desc'));

			$columns = array_fill_keys($order_by, array());

			foreach ($entries as $entry_id)
			{
				$data = $entries_lookup[$entry_id];

				foreach ($order_by as $k)
				{
					$column[$k][] = $data[$k];
				}
			}

			$sort = array_merge(
				array_fill_keys(array_keys($order_by), 'desc'),
				$sort
			);

			$sort_parameters = array();

			foreach ($order_by as $i => $v)
			{
				$sort_parameters[] = $column[$v];
				$sort_parameters[] = constant('SORT_'.strtoupper($sort[$i]));
			}

			$sort_parameters[] = &$entries;

			call_user_func_array('array_multisort', $sort_parameters);
		}


		// enforce offset and limit
		$offset = $this->param('offset');
		$limit = $this->param('limit');

		if ($limit)
		{
			$entries = array_slice($entries, $offset, $limit);
		}


		// limit entry ids to given tag
		// @todo @pk do this when propagating query ids?
		if ($this->param('entry_id') && ! $this->param('url_title'))
		{
			$allowed_ids = explode('|', $this->param('entry_id'));
			$entries = array_intersect($entries, $allowed_ids);
		}

		get_instance()->load->model('category_model');
		$categories = get_instance()->category_model->get_entry_categories($entries);

		foreach ($categories as &$cats)
		{
			foreach ($cats as &$cat)
			{
				if ( ! empty($cat))
				{
					$cat = array(
						$cat['cat_id'],
						$cat['parent_id'],
						$cat['cat_name'],
						$cat['cat_image'],
						$cat['cat_description'],
						$cat['group_id'],
						$cat['cat_url_title']
					);
				}
			}
		}

		// prefilter anything prefixed the same as this tag so that we don't
		// go around building huge lists with custom field data only to toss
		// it all because the tag isn't in the field.

		$singles = array();
		$doubles = array();

		$var_pair = get_instance()->TMPL->var_pair;
		$var_single = get_instance()->TMPL->var_single;

		$regex_prefix = '/^'.preg_quote($prefix, '/').'[^:]+( |$)/';

		foreach (preg_grep($regex_prefix, array_keys($var_single)) as $key)
		{
			$singles[$key] = $var_single[$key];
		}

		foreach (preg_grep($regex_prefix, array_keys($var_pair)) as $key)
		{
			$doubles[$key] = $var_pair[$key];
		}


		// Prep the chunk
		get_instance()->load->library('channel_entries_parser');
		$parser = get_instance()->channel_entries_parser->create($tagdata, $prefix);

		$preparsed = $parser->pre_parser($channel);

		// parse them
		foreach ($entries as $entry_id)
		{
			$count++;
			$data = $entries_lookup[$entry_id];

			$row_parser = $parser->row_parser($preparsed, $data);

			// @todo date parameters (i.e. show_expired=) need a query up above?
			// these may also need one, but for now this works
			$filter_parameters = array('author_id', 'channel', 'url_title', 'username', 'group_id', 'status');

			foreach ($filter_parameters as $p)
			{
				$filter_by = $this->param($p);

				if ( ! $filter_by)
				{
					continue;
				}

				$not = FALSE;

				if (strpos($filter_by, 'not ') === 0)
				{
					$not = TRUE;
				}

				// @todo support '&' inclusive stack
				$filter_by = explode('|', $filter_by);

				if ($p == 'channel')
				{
					$p = 'channel_name';
				}

				$data_matches = in_array($data[$p], $filter_by);

				if (($data_matches && $not) OR
					( ! $data_matches && ! $not))
				{
					continue 2;
				}
			}

			$variables = array();
			$cond_vars = array();

			$tagdata_chunk = $tagdata;

			// mod.channel 3357
			foreach ($doubles as $key => $val)
			{
				// parse {categories} pair
				$tagdata_chunk = $row_parser->parse_categories($key, $tagdata_chunk, $categories);
				
				// parse custom field pairs (file, checkbox, multiselect)
				$tagdata_chunk = $row_parser->parse_custom_field_pair($key, $tagdata_chunk);
			}

			// handle single custom field tags
			// @todo tag modifiers
			// @todo field-not-found fallback (mod.channel 4764)
			foreach ($singles as $key => $val)
			{
				// parse {switch} variable
				$tagdata_chunk = $row_parser->parse_switch_variable($key, $tagdata_chunk, $count);

				// parse non-custom dates ({entry_date}, {comment_date}, etc)
				$tagdata = $row_parser->parse_date_variables($key, $val, $tagdata);

				// parse simple variables that have parameters or special processing,
				// such as any of the paths, url_or_email, url_or_email_as_author, etc
				$tagdata_chunk = $row_parser->parse_simple_variables($key, $val, $tagdata_chunk);

				$replace = array();

				if ($val AND array_key_exists($val, $data))
				{
					$tagdata_chunk = get_instance()->swap_var_single($val, $data[str_replace($prefix, '', $val)], $tagdata_chunk);
				}

				// parse custom channel fields
				$tagdata_chunk = $row_parser->parse_custom_field($key, $val, $tagdata_chunk);
			}

			// special variables!
			$cond_vars[$prefix.'count'] = $count;
			$cond_vars[$prefix.'total_results'] = $total_results;

			$variables['{'.$prefix.'count}'] = $count;
			$variables['{'.$prefix.'total_results}'] = $total_results;

			$tagdata_chunk = str_replace(
				array_keys($variables),
				array_values($variables),
				$tagdata_chunk
			);

			// conditionals
			$tagdata_chunk = get_instance()->functions->prep_conditionals($tagdata_chunk, $cond_vars);
			unset($cond_vars);

			// child tags
			foreach ($children as $child)
			{
				$tagdata_chunk = $child->parse($entry_id, $tagdata_chunk, $entries_lookup);
			}

			$result .= $tagdata_chunk;
		}

		// kill prefixed leftovers
		$result = preg_replace('/{'.$prefix.'[^}]*}(.+?){\/'.$prefix.'[^}]*}/is', '', $result);
		$result = preg_replace('/{\/?'.$prefix.'[^}]*}/i', '', $result);

		// Lastly, handle the backspace parameter
		$backspace = $this->param('backspace');

		if ($backspace)
		{
			$result = substr($result, 0, -$backspace);
		}

		return $result;
	}
}

/**
 * We store a shortcut path to the kids that need their own queries:
 * http://en.wikipedia.org/wiki/Transitive_closure
 *
 */
class QueryNode extends ParseNode {

	private $closureChildren = array();

	// @override
	protected function _set_parent(EE_TreeNode $p)
	{
		parent::_set_parent($p);

		do
		{
			if ($p instanceOf QueryNode)
			{
				$p->addClosurePath($this);
				break;
			}

			$p = $p->parent();
		}
		while ($p);
	}

	public function closureChildren()
	{
		return $this->closureChildren;
	}

	public function addClosurePath(QueryNode $closureChild)
	{
		$this->closureChildren[] = $closureChild;
	}
}



// Does not iterate into query nodes
class ParseNodeIterator extends EE_TreeIterator {

	public function hasChildren()
	{
		if ( ! parent::hasChildren())
		{
			return FALSE;
		}

		$current = $this->current();
		$children = $current->children();

		foreach ($children as $kid)
		{
			if ( ! $kid instanceOf QueryNode)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	public function getChildren()
	{
		$current = $this->current();
		$children = array();

		foreach ($current->children() as $kid)
		{
			if ( ! $kid instanceOf QueryNode)
			{
				$children[] = $kid;
			}
		}

		// Using ref as per PHP source
		if (empty($this->ref))
		{
			$this->ref = new ReflectionClass($this);
		}

		return $this->ref->newInstance($children);
	}
}

// Iterates only query nodes
class QueryNodeIterator extends EE_TreeIterator {

	/**
	 * Override RecursiveArrayIterator's child detection method.
	 * We usually have data rows that are arrays so we really only
	 * want to iterate over those that match our custom format.
	 *
	 * @return boolean
	 */
	public function hasChildren()
	{
		$current = $this->current();

		if ( ! $current instanceOf QueryNode)
		{
			return FALSE;
		}

		$children = $current->closureChildren();

		return ! empty($children);
	}

	// --------------------------------------------------------------------

	/**
	 * Override RecursiveArrayIterator's get child method to skip
	 * ahead into the __children__ array and not try to iterate
	 * over the data row's individual columns.
	 *
	 * @return Object<TreeIterator>
	 */
	public function getChildren()
	{
		$current = $this->current();
		$children = $current->closureChildren();

		// Using ref as per PHP source
		if (empty($this->ref))
		{
			$this->ref = new ReflectionClass($this);
		}

		return $this->ref->newInstance($children);
	}
}

/* End of file Relationships.php */
/* Location: ./system/expressionengine/libraries/Relationships.php */