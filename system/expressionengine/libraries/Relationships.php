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