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

	public function __construct()
	{
		ee()->load->model('relationship_model');
	}

 	// --------------------------------------------------------------------

	/**
	 * Get a relationship parser and query object, populated with the
	 * information we'll need to parse out the relationships in this template.
	 *
	 * @param	The rfields array from the Channel Module at the time of parsing.
	 *
	 * @return Relationship_Parser	The parser object with the parsed out
	 *								hierarchy and all of the entry data.
	 */
	public function get_relationship_parser(array $relationship_fields, array $entry_ids)
	{
		$builder = new Relationship_tree_builder($relationship_fields);

		$tree = $builder->build_tree($entry_ids);

		if ($tree)
		{
			return $builder->get_parser($tree);
		}

		return NULL;
	}
}



/**
 *
 */
class Relationship_tree_builder {

	protected $_tree;
	protected $_unique_ids = array();					// all entry ids needed
	protected $relationship_field_ids = array();		// field_name => field_id
	protected $relationship_field_names = array();		// field_id => field_name

	/**
	 * Create a tree builder for the given relationship fields
	 */
	public function __construct(array $relationship_fields)
	{
		$this->relationship_field_ids = $relationship_fields;
		$this->relationship_field_names = array_flip($relationship_fields);
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
	public function build_tree(array $entry_ids)
	{
		// first, we need a tag tree
		$root = $this->_build_tree();

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

		// Ok, so now we need to repeat that process iteratively for
		// all of the special snowflakes on our tree. This is fun!

		$query_node_iterator = new RecursiveIteratorIterator(
			new QueryNodeIterator(array($root)),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($query_node_iterator as $node)
		{
			// the root uses the main entry ids, all others use all
			// of the parent's child ids. These form all of their potential
			// parents, and thus the where_in for our query.
			if ( ! $node->is_root())
			{
				$entry_ids = $node->parent()->entry_ids();
				$entry_ids = call_user_func_array('array_merge', $entry_ids);
			}

			// Store flattened ids for the big entry query
			$all_entry_ids[] = $this->_propagate_ids(
				$node,
				ee()->relationship_model->node_query($node, $entry_ids)
			);
		}

		$this->_unique_ids = array_unique(
			call_user_func_array('array_merge', $all_entry_ids),
			SORT_NUMERIC
		);

		return $root;
	}

	// --------------------------------------------------------------------

	public function get_parser(EE_TreeNode $root)
	{
		$unique_entry_ids = $this->_unique_ids;

		// @todo reduce to only those that have a categories pair or parameter
		ee()->load->model('category_model');
		$category_lookup = ee()->category_model->get_entry_categories($unique_entry_ids);

		// ready set, main query.
		ee()->load->model('channel_entries_model');
		$sql = ee()->channel_entries_model->get_entry_sql($unique_entry_ids);
		$entries_result = ee()->db->query($sql);

		// Build an id => data map for quick retrieval during parsing
		$entry_lookup = array();

		foreach ($entries_result->result_array() as $entry)
		{
			$entry_lookup[$entry['entry_id']] = $entry;
		}

		$entries_result->free_result();

		return new Relationship_parser($root, $entry_lookup, $category_lookup);
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
	protected function _build_tree()
	{
		// extract the relationship tags straight from the channel
		// tagdata so that we can process it all in one fell swoop.
		$str = ee()->TMPL->tagdata;

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

		if ( ! preg_match_all("/".LD.'\/?((?:(?:'.$all_fields.'):?)+)\b([^}{]*)?'.RD."/", $str, $matches, PREG_SET_ORDER))
		{
			return NULL;
		}

		// nesting trackers
		// this code would probably be a little prettier with a state machine
		// instead of the crazy regex.
		$uuid = 1;
		$id_stack = array(0);
		$rel_stack = array();

		$root = new QueryNode('__root__');
		$nodes = array($root);

		foreach ($matches as $match)
		{
			$relationship_prefix = $match[1];

			// some helpful booleans
			$is_closing				= ($match[0][1] == '/');
			$is_only_relationship	= (substr($relationship_prefix, -1) != ':');

			// catch closing tags right away, we don't need them
			if ($is_closing)
			{
				// closing a relationship tag - pop the stacks
				if ($is_only_relationship)
				{
					array_pop($rel_stack);
					array_pop($id_stack);
				}

				continue;
			}

			$tag_name = rtrim($relationship_prefix, ':');

			// Opening tags are a little harder, it's a shortcut if it has
			// a non prefix portion and the prefix does not yet exist on the
			// stack. Otherwise it's a field we can safely skip.
			// Of course, if it has no tag, it's definitely a relationship
			// field and we have to track it.

			if ( ! $is_only_relationship && in_array($tag_name, $rel_stack))
			{
				continue;
			}


			list($tag, $parameters) = preg_split("/\s+/", $match[2].' ', 2);
			$parent_id = end($id_stack);

			// no closing tag tracking for shortcuts
			if ($is_only_relationship)
			{
				$id_stack[] = ++$uuid;
				$rel_stack[] = $tag_name;
			}

			// extract the full name and determining relationship
			$last_colon = strrpos($tag_name, ':');

			if ($last_colon === FALSE)
			{
				$determinant_relationship = $tag_name;
			}
			else
			{
				$determinant_relationship = substr($tag_name, $last_colon + 1);
			}

			// prep parameters
			$params = ee()->functions->assign_parameters($parameters);
			$params = $params ? $params : array();

			// setup node type
			// if it's a root sibling tag, or the determining relationship
			// is parents then we need to do a new query for them
			$node_class = 'ParseNode';

			if ($determinant_relationship == 'parents' OR $tag_name == 'siblings')
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
				'open_tag'	=> $match[0]
			));

			$nodes[$uuid] = $node;
			$parent = $nodes[$parent_id];
			$parent->add($node);
		}

		// Doing our own parsing let's us do error checking
		if (count($rel_stack))
		{
			throw new RelationshipException('Unmatched Relationship Tag: "{'.end($rel_stack).'}"');
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
	 * @param	array	Leave path array as created by _parse_leaves
	 * @return	array	All unique entry ids processed.
	 */
	protected function _propagate_ids(QueryNode $root, array $leave_paths)
	{
		$parse_node_iterator = new RecursiveIteratorIterator(
			new ParseNodeIterator(array($root)),
			RecursiveIteratorIterator::SELF_FIRST
		);

		$root_offset = 0;

		$all_entry_ids = array();
		$leaves = $this->_parse_leaves($leave_paths);

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
						$field_ids[] = $this->relationship_field_ids[$name];
					}
				}
				else
				{
					// no parameter, everything is fair game
					$field_ids = array_keys($leaves[$depth]);
				}
			}
			else
			{
				$field_ids = array(
					$this->relationship_field_ids[$node->field_name]
				);
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

			$all_entry_ids[] = call_user_func_array('array_merge', $node->entry_ids());
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
}




class Relationship_parser {

	protected $_tree;
	protected $_entries;
	protected $_categories;

	public function __construct($tree, $entries, $categories)
	{
		$this->_tree = $tree;
		$this->_entries = $entries;
		$this->_categories = $categories;
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Entry data accessor.
	 *
	 * Utility method to retrieve an entry from our cached query data.
	 *
	 * @param	int		The id of the entry to retrieve
	 * @return 	array	Row data for the requested entry.
	 */
	public function entry($id)
	{
		return $this->_entries[$id];
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Category data accessor.
	 *
	 * Utility method to retrieve a category from our cached query data.
	 *
	 * @param	int		The id of the category to retrieve
	 * @return 	array	Category data for the requested category.
	 */
	public function category($id)
	{
		return isset($this->_categories[$id]) ? $this->_categories[$id] : NULL;
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
	public function parse($entry_id, $tagdata, $channel)
	{
		$node = $this->_tree;

		// If we have no relationships, then we can quietly bail out.
		if (empty($this->_entries))
		{
			return $this->clear_node_tagdata($node, $tagdata);
		}

		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');
		ee()->session->set_cache('relationships', 'channel', $channel);

		// push the root node down right away
		if ( ! $node->is_root())
		{
			throw new RelationshipException('Invalid Relationship Tree');
		}

		foreach ($node->children() as $child)
		{
			$tagdata = $this->parse_node($child, $entry_id, $tagdata);
		}

		return $tagdata;
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Parse an individual tree node. Will loop through each chunk that
	 * applies to this node and call the channel entries parser on it.
	 *
	 * @param	object	The node to parse
	 * @param	int		The id of the parent entry we're working with.
	 * @param	string	The tagdata to parse. We zero in on the chunks
	 *					that apply to this tag for better performance.
	 *
	 * @return 	string	The parsed tagdata.
	 */
	public function parse_node($node, $parent_id, $tagdata)
	{
		if ( ! isset($node->entry_ids[$parent_id]))
		{
			return $this->clear_node_tagdata($node, $tagdata);
		}

		$tag = preg_quote($node->name(), '/');

		if ($node->shortcut)
		{
			if (preg_match_all('/'.$node->open_tag.'(.+?){\/'.$tag.':'.$node->shortcut.'}/is', $tagdata, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as &$match)
				{
					$match = array($match[0], $match[0]);
				}
			}
			else
			{
				$matches = array(array($node->open_tag, $node->open_tag));
			}

			$entry_ids = $node->entry_ids();
			$entry_id = reset($entry_ids[$parent_id]);

			$categories = array();

			if (isset($this->_categories[$entry_id]))
			{
				$categories[$entry_id] = $this->category($entry_id);
			}

			// put categories into the weird form the channel module uses
			// @todo take db results directly
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

			$data = array(
				'entries' => array($entry_id => $this->entry($entry_id)),
				'categories' => $categories
			);
		}
		else
		{
			if ( ! preg_match_all('/'.$node->open_tag.'(.+?){\/'.$tag.'}/is', $tagdata, $matches, PREG_SET_ORDER))
			{
				return $tagdata;
			}

			$data = $this->process_parameters($node, $parent_id);
		}

		foreach ($matches as $match)
		{
			$tagdata = str_replace(
				$match[0],
				$this->replace($node, $match[1], $data),
				$tagdata
			);
		}

		return $tagdata;
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Deletes the node tags from the given template.
	 *
	 * Used for empty nodes so that we don't end up with unparsed tags
	 * all over the place.
	 *
	 * @param	object	The tree node of this tag pair
	 * @param	string	The tagdata to delete the tags from.
	 * @return 	string	The cleaned tagdata
	 */
	public function clear_node_tagdata($node, $tagdata)
	{
		$tag = preg_quote($node->name(), '/');
		
		$tagdata = preg_replace('/'.$node->open_tag.'(.+?){\/'.$tag.'}/is', '', $tagdata);
		return str_replace($node->open_tag, '', $tagdata);
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Process the parameters of this tag pair to figure out what data
	 * we need, and in what order.
	 *
	 * @param	object	The tree node of this tag pair
	 * @param	int		The relative parent id. Its children will
	 *					be considered for processing.
	 * @return 	array	The data array that the channel parser expects.
	 */
	public function process_parameters($node, $parent_id)
	{
		// we use this to parse child nodes from the parser
		$node->parser = $this;

		$entry_ids = $node->entry_ids();
		$entry_ids = $entry_ids[$parent_id];

		// reorder the ids
		if ($node->param('orderby'))
		{
			$entry_ids = $this->_apply_sort($node, $entry_ids);
		}

		// enforce offset and limit
		$offset = $node->param('offset');
		$limit = $node->param('limit');

		if ($limit)
		{
			$entry_ids = array_slice($entry_ids, $offset, $limit);
		}

		// prefilter anything prefixed the same as this tag so that we don't
		// go around building huge lists with custom field data only to toss
		// it all because the tag isn't in the field.

		$rows = array();
		$categories = array();

		$filter_parameters = array(
			'author_id', 'channel', 'url_title', 'username', 'group_id', 'status'
		);

		foreach ($entry_ids as $entry_id)
		{
			$data = $this->entry($entry_id);

			// @todo date parameters (i.e. show_expired=) need a query up above?
			// these may also need one, but for now this works

			foreach ($node->params as $p)
			{
				if ( ! in_array($p, $filter_parameters))
				{
					continue;
				}

				$filter_by = $node->param($p);

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

			$rows[$entry_id] = $data;

			if (isset($this->_categories[$entry_id]))
			{
				$categories[$entry_id] = $this->category($entry_id);
			}
		}

		// put categories into the weird form the channel module uses
		// @todo take db results directly
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

		return array(
			'entries' => $rows,
			'categories' => $categories,
		);
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Call the channel entries parser for this node and its tagchunk.
	 *
	 * @param	object	The tree node of this tag pair
	 * @param	int		The chunk of template to process.
	 * @param 	array	The data array that the channel parser expects.
	 *
	 * @return	string	The parsed chunk
	 */
	public function replace($node, $tagdata, $data)
	{
		$prefix = $node->name().':';
		$channel = ee()->session->cache('relationships', 'channel');

		// Load the parser
		ee()->load->library('channel_entries_parser');
		$parser = ee()->channel_entries_parser->create($tagdata, $prefix);
		
		$config = array(
			'callbacks' => array(
				'tagdata_loop_end' => array($node, 'callback_tagdata_loop_end')
			),
			'disable' => array(
				'relationships'
			)
		);

		$result = $parser->parse($channel, $data, $config);

		// Lastly, handle the backspace parameter
		$backspace = $node->param('backspace');

		if ($backspace)
		{
			$result = substr($result, 0, -$backspace);
		}

		return $result;
	}

 	// --------------------------------------------------------------------
	
	/**
	 * Utility method to do the row sorting in PHP.
	 *
	 * @param	object	The current tree node
	 * @param 	array	The list of entry ids that we're sorting.
	 *
	 * @return	string	The sorted entry id list
	 */
	public function _apply_sort($node, $entry_ids)
	{
		$order_by = explode('|', $node->param('orderby'));
		$sort = explode('|', $node->param('sort', 'desc'));

		$columns = array_fill_keys($order_by, array());

		foreach ($entry_ids as $entry_id)
		{
			$data = $this->entry($entry_id);

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

		$sort_parameters[] = &$entry_ids;

		call_user_func_array('array_multisort', $sort_parameters);

		return $entry_ids;
	}
}





class ParseNode extends EE_TreeNode {

	private $_dirty;
	private $_parser;
	private $_entry_id_fn;
	private $_entry_id_opts;

	public function __construct($name, $payload = NULL)
	{
		parent::__construct($name, $payload);

		// if entry_id="4|5|6" was given, we filter them here
		$parameter = $this->param('entry_id');

		if ($parameter)
		{
			$this->_entry_id_fn = 'array_intersect';

			if (strncasecmp($parameter, 'not ', 4) == 0)
			{
				$this->_entry_id_fn = 'array_diff';
				$parameter = substr($parameter, 4);
			}

			$parameter = trim($parameter, " |\r\n\t");
			$this->_entry_id_opts = explode('|', $parameter);
		}
	}

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

		$this->_dirty = TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Entry id accessor
	 *
	 * Ensures that only ids that are allowed by the entry_id= parameter
	 * are processed. This used to be in the setter, but it ends up being
	 * quite an expensive operation.
	 *
	 * @param 	int		the parent entry id
	 * @return 	[int]	child ids | flattened if no parent_id was given
	 */
	public function entry_ids()
	{
		$ids =& $this->data['entry_ids'];

		if ($this->_dirty)
		{
			if (isset($this->_entry_id_opts))
			{
				$fn = $this->_entry_id_fn;
				$opts = $this->_entry_id_opts;

				foreach ($ids as $parent => $children)
				{
					$ids[$parent] = array_unique(
						$fn($children, $opts),
						SORT_NUMERIC
					);
				}
			}

			$this->_dirty = FALSE;
		}

		if (isset($parent_id))
		{
			return isset($ids[$parent_id]) ? $ids[$parent_id] : NULL;
		}

		return $ids;
	}

	// --------------------------------------------------------------------

	/**
	 * At the end of the channel entries parsing loop we need to recurse
	 * into the child tags of our tree. The relationship_parser would need
	 * to manually keep track of the stack as the parsing happens depth
	 * first. It's much easier to bridge it here.
	 *
	 * @param 	string	tagdata for the children to parse
	 * @param	array	the current channel entries row
	 * @return 	string	child parsed tagdata
	 */
	public function callback_tagdata_loop_end($tagdata, $row)
	{
		foreach ($this->children() as $child)
		{
			$tagdata = $this->parser->parse_node($child, $row['entry_id'], $tagdata);
		}

		return $tagdata;
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

class RelationshipException extends RuntimeException {}

/* End of file Relationships.php */
/* Location: ./system/expressionengine/libraries/Relationships.php */
