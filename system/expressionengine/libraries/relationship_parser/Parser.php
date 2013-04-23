<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Data Parser Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Relationship_data_parser {

	protected $_tree;
	protected $_entries;
	protected $_categories;

	protected $_channel;

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
		$this->_channel = $channel;

		// push the root node down right away
		if ( ! $node->is_root())
		{
			throw new EE_Relationship_exception('Invalid Relationship Tree');
		}

		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');

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
		$this->find_no_results($node, $tagdata);

		if ( ! isset($node->entry_ids[$parent_id]))
		{
			return $this->clear_node_tagdata($node, $tagdata);
		}

		$tag = preg_quote($node->name(), '/');
		$open_tag = preg_quote($node->open_tag, '/');

		if ($node->shortcut)
		{
			$entry_ids = $node->entry_ids();
			$entry_ids = array_unique($entry_ids[$parent_id]);
			$entry_id = reset($entry_ids);

			if (preg_match_all('/'.$open_tag.'(.+?){\/'.$tag.':'.$node->shortcut.'}/is', $tagdata, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as &$match)
				{
					$match = array($match[0], $match[0]);
				}
			}
			else
			{
				// Common single tag used outside the loop tag, we do it
				// here because the parser only gets one entry for shortcut
				// pairs. It's also much faster compared to spinning up the
				// channel entries parser.
				if ($node->shortcut == 'total_results')
				{
					return str_replace(
						$node->open_tag,
						count($entry_ids),
						$tagdata
					);
				}

				$matches = array(array($node->open_tag, $node->open_tag));
			}

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
			if ( ! preg_match_all('/'.$open_tag.'(.+?){\/'.$tag.'}/is', $tagdata, $matches, PREG_SET_ORDER))
			{
				return $tagdata;
			}

			$data = $this->process_parameters($node, $parent_id);

			if ( ! count($data['entries']))
			{
				return $this->clear_node_tagdata($node, $tagdata);
			}
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
	 * Assign no results data to the node.
	 *
	 * Find the no_results tag once per node. This is more future proofing
	 * than anything, currently it makes very little difference where we
	 * pull it out.
	 *
	 * @param	object	The tree node of this tag pair
	 * @param	string	The tagdata to delete the tags from.
	 * @return 	void
	 */
	public function find_no_results($node, $tagdata)
	{
		if (isset($node->no_results))
		{
			return;
		}

		$tag = preg_quote($node->name(), '/');

		// Find no results chunks
		$has_no_results = strpos($tagdata, 'if '.$node->name().':no_results') !== FALSE;

		if ($has_no_results && preg_match("/".LD."if {$tag}:no_results".RD."(.*?)".LD.'\/'."if".RD."/s", $tagdata, $match))
		{
			$node->no_results = $match;
			return;
		}

		$node->no_results = FALSE;
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
		$channel = $this->_channel;

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

		return $this->cleanup_no_results_tag($node, $result);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes the node tags from the given template and replace it with
	 * the no_results tag if it exists.
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
		$open_tag = preg_quote($node->open_tag, '/');

		if ($node->shortcut)
		{
			$tagdata = str_replace($node->open_tag, '', $tagdata);
		}

		if ( ! preg_match_all('/'.$open_tag.'(.+?){\/'.$tag.'}/is', $tagdata, $matches, PREG_SET_ORDER))
		{
			return $tagdata;
		}

		$no_results = $node->no_results;
		$no_results = empty($no_results) ? '' : $node->no_results[1];

		foreach ($matches as $match)
		{
			$tagdata = substr_replace($tagdata, $no_results, strpos($tagdata, $match[0]), strlen($match[0]));
		}

		$tagdata = preg_replace('/'.$open_tag.'(.+?){\/'.$tag.'}/is', '', $tagdata);
		return str_replace($node->open_tag, '', $tagdata);
	}

	// --------------------------------------------------------------------

	/**
	 * Deletes the node tags from the given template and replace it with
	 * the no_results tag if it exists.
	 *
	 * Used for empty nodes so that we don't end up with unparsed tags
	 * all over the place.
	 *
	 * @param	object	The tree node of this tag pair
	 * @param	string	The tagdata to delete the tags from.
	 * @return 	string	The cleaned tagdata
	 */
	public function cleanup_no_results_tag($node, $tagdata)
	{
		$no_results = $node->no_results;

		if ( ! empty($no_results))
		{
			$tagdata = str_replace($no_results[0], '', $tagdata);
		}

		return $tagdata;
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

		// make sure defaults are set
		if ( ! $node->param('status'))
		{
			$node->set_param('status', 'open');
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

			if ($node->param('show_future_entries') != 'yes')
			{
				if ($data['entry_date'] > ee()->localize->now)
				{
					continue;
				}
			}

			if ($node->param('show_expired') != 'yes')
			{
				if ($data['expiration_date'] != 0 AND $data['expiration_date'] < ee()->localize->now)
				{
					continue;
				}
			}

			foreach ($node->params as $p => $value)
			{
				if ($p == 'start_on' OR $p == 'stop_before')
				{
					$sign = ($p == 'start_on') ? -1 : 1;
					$diff = $data['entry_date'] - ee()->localize->string_to_timestamp($value);

					if ($diff * $sign > 0)
					{
						continue 2;
					}
				}

				if ( ! in_array($p, $filter_parameters))
				{
					continue;
				}

				if ( ! $value)
				{
					continue;
				}

				$not = FALSE;

				if (strpos($value, 'not ') === 0)
				{
					$not = TRUE;
				}

				$value = trim($value,  " |\t\n\r");
				$value = explode('|', $value);

				if ($p == 'channel')
				{
					$p = 'channel_name';
				}

				$data_matches = in_array($data[$p], $value);

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

		// random
		if ($order_by[0] == 'random')
		{
			shuffle($entry_ids);
			return $entry_ids;
		}

		// custom field
		$channel = $this->_channel;

		foreach($channel->cfields as $site_id => $cfields)
		{
			foreach ($order_by as &$key)
			{
				if (isset($cfields[$key]))
				{
					$key = 'field_id_'.$cfields[$key];
				}
			}
		}

		// split into columns
		$columns = array_fill_keys($order_by, array());

		foreach ($entry_ids as $entry_id)
		{
			$data = $this->entry($entry_id);

			foreach ($order_by as &$k)
			{
				$k = ($k == 'date') ? 'entry_date' : $k;

				$columns[$k][] = $data[$k];
			}
		}

		// default everyting to desc
		$sort = $sort + array_fill_keys(array_keys($order_by), 'desc');

		// fill array_multisort parameters
		$sort_parameters = array();

		foreach ($order_by as $i => $v)
		{
			$sort_parameters[] = $columns[$v];
			$sort_parameters[] = constant('SORT_'.strtoupper($sort[$i]));
		}

		$sort_parameters[] = &$entry_ids;

		call_user_func_array('array_multisort', $sort_parameters);

		return $entry_ids;
	}
}

/* End of file Parser.php */
/* Location: ./system/expressionengine/libraries/relationship_parser/Parser.php */