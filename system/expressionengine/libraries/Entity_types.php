<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.7
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Entity Types Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Entity_types {

	private $_table = 'entity_types';

	// --------------------------------------------------------------------

	/**
	 * Add an entity
	 *
	 * Gives the fieldtypes an opportunity to do one-time prep work for
	 * new entity types.
	 *
	 * Third parties are responsible for calling this when their module
	 * is installed.
	 *
	 * @param	string	Name of the entity being removed
	 * @return	void
	 */
	public function register($name)
	{
		if ( ! $name || $name == 'channel')
		{
			return;
		}

		$param = compact('name');

		ee()->db->insert($this->_table, $param);

		$this->_notify_fieldtypes('unregister_entity', $param);
	}

	// --------------------------------------------------------------------

	/**
	 * Remove an entity
	 *
	 * Tells all fieldtypes that they need to potentially do some
	 * cleanup work. The fieldtype is responsible for correctly interpreting
	 * this signal. The module is responsible for calling this method when
	 * it is uninstalled.
	 *
	 * @param	string	Name of the entity being removed
	 * @return	void
	 */
	public function unregister($name)
	{
		if ( ! $name || $name == 'channel')
		{
			return;
		}

		$param = compact('name');

		ee()->db->delete($this->_table, $param);

		if (ee()->db->affected_rows())
		{
			$this->_notify_fieldtypes('register_entity', $param);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Retrieve a list of all entity types
	 */
	public function all()
	{
		return array_unique(array_map(
			'array_pop',
			ee()->db->select('name')->get($this->_table)->result_array()
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Notify all fieldtypes of the entity change.
	 *
	 * @param	string	Name of the function to call ([un]register_entity)
	 * @param	string	Name of the entity being modified
	 * @return	void
	 */
	private function _notify_fieldtypes($fn, $param)
	{
		ee()->load->library('api');
		ee()->api->instantiate('channel_fields');
		$ft_api = ee()->api_channel_fields;

		$fts = $ft_api->fetch_installed_fieldtypes();

		foreach($fts as $key => $attr)
		{
			if ($ft_api->setup_handler($key))
			{
				if ($ft_api->apply('accepts_entity', $param))
				{
					$ft_api->apply($fn, $param);
				}
			}
		}
	}
}