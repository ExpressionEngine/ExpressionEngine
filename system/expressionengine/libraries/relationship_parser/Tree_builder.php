<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

 require_once APPPATH.'libraries/datastructures/Tree.php';
 require_once APPPATH.'libraries/relationship_parser/Nodes.php';
 require_once APPPATH.'libraries/relationship_parser/Iterators.php';

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Tree Builder Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_relationship_tree_builder {

	protected $_tree;
	protected $_unique_ids = array();					// all entry ids needed
	protected $relationship_field_ids = array();		// field_name => field_id
	protected $relationship_field_names = array();		// field_id => field_name

	protected $grid_relationship_ids = NULL;			// gridprefix:field_name => grid_field_id
	protected $grid_relationship_names = NULL;			// grid_field_id => gridprefix:field_name
	protected $grid_field_id = NULL;

	/**
	 * Create a tree builder for the given relationship fields
	 */
	public function __construct(array $relationship_fields, array $grid_relationships = array(), $grid_field_id = NULL)
	{
		foreach ($relationship_fields as $site_id => $fields)
		{
			foreach ($fields as $name => $id)
			{
				if ( ! isset($this->relationship_field_ids[$name]))
				{
					$this->relationship_field_ids[$name] = array();
				}

				$this->relationship_field_ids[$name][] = $id;
				$this->relationship_field_names[$id] = $name;
			}
		}

		$this->grid_relationship_ids = $grid_relationships;
		$this->grid_relationship_names = array_flip($grid_relationships);
		$this->grid_field_id = $grid_field_id;
	}

	// --------------------------------------------------------------------

	/**
	 * Find All Relationships of the Given Entries in the Template
	 *
	 * Searches the template the parser was constructed with for relationship
	 * tags and then builds a tree of all the requested related entries for
	 * each of the entries passed in the array.
	 *
	 * For space savings and subtree querying each node is pushed
	 * its own set of entry ids per parent ids:
	 *
	 *						 {[6, 7]}
	 *						/		\
	 *		 {6:[2,4], 7:[8,9]}    	{6:[], 7:[2,5]}
	 *				/					\
	 *	  		...				  		 ...
	 *
	 * By pushing them down like this the subtree query is very simple.
	 * And when we parse we simply go through all of them and make that
	 * many copies of the node's tagdata.
	 *
	 * @param	int[]	An array of entry ids who's relations we need
	 *					to find.
	 * @return	object	The tree root node
	 */
	public function build_tree(array $entry_ids, $tagdata)
	{
		// first, we need a tag tree
		$root = $this->_build_tree($tagdata);

		if ($root === NULL)
		{
			return NULL;
		}

		// not strictly necessary, but keeps all the id loops parent => children
		// it has no side-effects since all we really care about for the root
		// node are the children.
		foreach ($entry_ids as $id)
		{
			$root->add_entry_id((int)$id, (int)$id);
		}

		$all_entry_ids = array($entry_ids);

		if (isset($this->grid_field_id))
		{
			$all_entry_ids = array(array());
		}

		$query_node_iterator = new RecursiveIteratorIterator(
			new QueryNodeIterator(array($root)),
			RecursiveIteratorIterator::SELF_FIRST
		);

		// For every query node we now run the query and push the ids
		// down onto their subtrees.
		foreach ($query_node_iterator as $node)
		{
			// the root uses the main entry ids, all others use all
			// of the parent's child ids. These form all of their potential
			// parents, and thus the where_in for our query.
			if ( ! $node->is_root() && ! $node->in_grid)
			{
				$entry_ids = $node->parent()->entry_ids();
				$entry_ids = call_user_func_array('array_merge', $entry_ids);
			}

			// Store flattened ids for the big entry query
			$all_entry_ids[] = $this->_propagate_ids(
				$node,
				ee()->relationship_model->node_query($node, $entry_ids, $this->grid_field_id)
			);
		}

		$this->_unique_ids = ee_array_unique(
			call_user_func_array('array_merge', $all_entry_ids),
			SORT_NUMERIC
		);

		return $root;
	}

	// --------------------------------------------------------------------

	/**
	 * Create a parser from our collected tree.
	 *
	 * Runs the queries using our id information, builds lookup tables,
	 * and finally stick it all onto an object that knows what to do with
	 * it.
	 *
	 * @param	object	Root query node of the relationship tree
	 * @return	object	The new relationships parser
	 */
	public function get_parser(EE_TreeNode $root)
	{
		$unique_entry_ids = $this->_unique_ids;

		$category_lookup = array();
		$entries_result = array();

		if ( ! empty($unique_entry_ids))
		{
			// @todo reduce to only those that have a categories pair or parameter
			ee()->load->model('category_model');
			$category_lookup = ee()->category_model->get_entry_categories($unique_entry_ids);

			// ready set, main query.
			ee()->load->model('channel_entries_model');
			$entries_result = ee()->channel_entries_model->get_entry_data($unique_entry_ids);
		}

		// Build an id => data map for quick retrieval during parsing
		$entry_lookup = array();

		foreach ($entries_result as $entry)
		{
			$entry_lookup[$entry['entry_id']] = $entry;
		}

		// -------------------------------------------
		// 'relationships_query_result' hook.
		//  - Take the whole relationship result array, do what you wish
		//  - added 2.7.1
		//
			if (ee()->extensions->active_hook('relationships_query_result') === TRUE)
			{
				$entry_lookup = ee()->extensions->call('relationships_query_result', $entry_lookup);
				if (ee()->extensions->end_script === TRUE) return NULL;
			}
		//
		// -------------------------------------------


		if ( ! class_exists('EE_Relationship_data_parser'))
		{
			require_once APPPATH.'libraries/relationship_parser/Parser.php';
		}

		return new EE_Relationship_data_parser($root, $entry_lookup, $category_lookup);
	}

	// --------------------------------------------------------------------

	/**
	 * Turn the tagdata hierarchy into a tree
	 *
	 * Looks through the tagdata string to find all of the relationship
	 * tags that we might use and constructs a tree hierachy from them.
	 *
	 * @param	array	Entry ids
	 * @return	object	Root node of the final tree
	 */
	protected function _build_tree($str)
	{
		// No variables?  No reason to continue...
		if (strpos($str, '{') === FALSE)
		{
			return NULL;
		}

		$all_fields = $this->relationship_field_names;
		$all_fields = implode('|', $all_fields).'|parents|siblings';

		// Regex to separate out the relationship prefix part from the rest
		// {rel:pre:fix:tag:modified param="value"}
		// 0 => full_match
		// 1 => rel:pre:fix:
		// 2 => tag:modified param="value"
		$is_grid = ( ! empty($this->grid_relationship_names));

		if ( ! $is_grid)
		{
			$regex = "/".LD.'\/?((?:(?:'.$all_fields.'):?)+)\b([^}{]*)?'.RD."/";
		}
		else
		{
			$force_parent = implode('|', $this->grid_relationship_names);
			$regex = "/".LD.'\/?('.$force_parent.'(?:[:](?:(?:'.$all_fields.'):?)+)?)\b([^}{]*)?'.RD."/";
		}

		if ( ! preg_match_all($regex, $str, $matches, PREG_SET_ORDER))
		{
			return NULL;
		}

		$root = new QueryNode('__root__');

		$open_nodes = array(
			'__root__' => $root
		);

		foreach ($matches as $match)
		{
			$relationship_prefix = $match[1];

			// some helpful booleans
			$is_closing				= ($match[0][1] == '/');
			$is_only_relationship	= (substr($relationship_prefix, -1) != ':');

			$tag_name = rtrim($relationship_prefix, ':');
			$in_grid = array_key_exists($relationship_prefix, $this->grid_relationship_ids);

			if ($in_grid && $match[2])
			{
				$is_only_relationship = ($match[2][0] != ':');
			}


			// catch closing tags right away, we don't need them
			if ($is_closing)
			{
				// closing a relationship tag - remove from open
				if ($is_only_relationship)
				{
					unset($open_nodes[$tag_name]);
				}

				continue;
			}

			// Opening tags are a little harder, it's a shortcut if it has
			// a non prefix portion and the prefix does not yet exist on the
			// stack. Otherwise it's a field we can safely skip.
			// Of course, if it has no tag, it's definitely a relationship
			// field and we have to track it.

			if ( ! $is_only_relationship && isset($open_nodes[$tag_name]))
			{
				continue;
			}

			// extract the full name and determining relationship
			$last_colon = strrpos($tag_name, ':');
			$in_grid = array_key_exists($relationship_prefix, $this->grid_relationship_ids);

			if ($last_colon === FALSE || $in_grid)
			{
				$parent_node = $open_nodes['__root__'];
				$determinant_relationship = $tag_name;
			}
			else
			{
				$parent_node = $open_nodes[substr($tag_name, 0, $last_colon)];
				$determinant_relationship = substr($tag_name, $last_colon + 1);
			}

			// prep parameters
			list($tag, $parameters) = preg_split("/\s+/", $match[2].' ', 2);
			$params = ee()->functions->assign_parameters($parameters);
			$params = $params ? $params : array();


			// setup node type
			// if it's a root sibling tag, or the determining relationship
			// is parents then we need to do a new query for them
			$node_class = 'ParseNode';

			if ($determinant_relationship == 'parents' OR $tag_name == 'siblings' OR $in_grid)
			{
				$node_class = 'QueryNode';
			}

			// instantiate and hook to tree
			$node = new $node_class($tag_name, array(
				'field_name'=> $determinant_relationship,
				'tag_info'	=> array(),
				'entry_ids'	=> array(),
				'params'	=> $params,
				'shortcut'	=> $is_only_relationship ? FALSE : $tag,
				'open_tag'	=> $match[0],
				'in_grid'	=> $in_grid
			));

			if ($is_only_relationship)
			{
				$open_nodes[$tag_name] = $node;
			}

			$parent_node->add($node);
		}

		// Doing our own parsing let's us do error checking
		if (count($open_nodes) > 1)
		{
			$open = array_pop($open_nodes);
			throw new EE_Relationship_exception('Unmatched Relationship Tag: "{'.$open->name().'}"');
		}

		return $root;
	}

	// --------------------------------------------------------------------

	/**
	 * Push the id graph onto the tag graph.
	 *
	 * Given the possible ids of a query node and the leave paths of
	 * all of its children, we can generate parent > children pairs
	 * for all of the descendent parse nodes.
	 *
	 * @param	object	Root query node whose subtree to process
	 * @param	array	Raw unstructured database result from exp_relationships
	 * @return	array	All unique entry ids processed.
	 */
	protected function _propagate_ids(QueryNode $root, array $db_result)
	{
		$parse_node_iterator = new RecursiveIteratorIterator(
			new ParseNodeIterator(array($root)),
			RecursiveIteratorIterator::SELF_FIRST
		);

		$root_offset = 0;

		$all_entry_ids = array();
		$leaves = $this->_parse_leaves($db_result);

		foreach ($parse_node_iterator as $node)
		{
			$depth = $parse_node_iterator->getDepth();

			if ($node->is_root())
			{
				$root_offset = -1;
				continue;
			}

			$is_root_sibling = ($node->name() == 'siblings'); // unprefixed {sibling}

			// If the tag is prefixed:sibling, then we already have the ids
			// on the parent since our query is not limited in breadth.
			// This does not apply to an un-prefixed sibling tag which is
			// handled as regular subtree below.
			if ($node->field_name == 'siblings' &&  ! $is_root_sibling)
			{
				$siblings = array();
				$possible_siblings = $node->parent()->entry_ids();

				foreach ($possible_siblings as $parent => $children)
				{
					$children = array_unique($children);

					// find all sibling permutations
					for ($i = 0; $i < count($children); $i++)
					{
						$no_sibs = $children;
						list($key) = array_splice($no_sibs, $i, 1);
						$node->add_entry_id($key, $no_sibs);
					}
				}

				continue;
			}

			// the lookup below starts one up from the root
			$depth += $root_offset;
			$field_ids = NULL;

			// if the field contains parent or is siblings, we need to check
			// for the optional field= parameter.
			if ($node->field_name == 'parents' OR $node->field_name == 'siblings')
			{
				$field_ids = array();
				$field_name = $node->param('field');

				if ($field_name)
				{
					foreach (explode('|', $field_name) as $name)
					{
						foreach ($this->relationship_field_ids[$name] as $rel_field_id)
						{
							$field_ids[] = $rel_field_id;
						}
					}
				}
				elseif (isset($leaves[$depth]))
				{
					// no parameter, everything is fair game
					$field_ids = array_keys($leaves[$depth]);
				}
			}
			elseif ($node->in_grid)
			{
				$field_ids = array(
					$this->grid_relationship_ids[$node->field_name]
				);
			}
			else
			{
				$field_ids = $this->relationship_field_ids[$node->field_name];
			}

			// propogate the ids
			foreach ($field_ids as $field_id)
			{
				if (isset($leaves[$depth][$field_id]))
				{
					foreach ($leaves[$depth][$field_id] as $parent => $children)
					{
						foreach ($children as $child)
						{
							$child_id = $child['id'];

							if ($is_root_sibling && $parent == $child_id)
							{
								continue;
							}

							$node->add_entry_id($parent, $child_id);
						}
					}
				}
			}

			$entry_ids = $node->entry_ids;

			if ( ! empty($entry_ids))
			{
				$all_entry_ids[] = call_user_func_array('array_merge', $entry_ids);
			}
		}

		if ( ! count($all_entry_ids))
		{
			return array();
		}

		return call_user_func_array('array_merge', $all_entry_ids);
	}

	// --------------------------------------------------------------------

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
				$entry_id = (int) $leaf['L'.$i.'_id'];
				$parent_id = (int) $leaf['L'.$i.'_parent'];

				if ($entry_id == NULL)
				{
					break;
				}

				if ($i == 0 && $leaf['L0_grid_col_id'])
				{
					$field_name = $this->grid_relationship_names[$field_id];
				}
				else
				{
					$field_name = $this->relationship_field_names[$field_id];
				}

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
}

/* End of file Tree_builder.php */
/* Location: ./system/expressionengine/libraries/relationship_parser/Tree_builder.php */