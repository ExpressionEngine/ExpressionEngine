<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_update_member_data_column_names',
				'_add_snippet_edit_date'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Fields created in 3.0 were missing the 'm_' prefix on their data columns,
	 * so we need to add the prefix back
	 */
	private function _update_member_data_column_names()
	{
		$member_data_columns = ee()->db->list_fields('member_data');

		$columns_to_modify = array();
		foreach ($member_data_columns as $column)
		{
			if ($column == 'member_id' OR 						// Don't rename the primary key
				substr($column, 0, 2) == 'm_' OR 				// or if it already has the prefix
				in_array('m_'.$column, $member_data_columns)) 	// or if the prefixed column already exists (?!)
			{
				continue;
			}

			$columns_to_modify[$column] = array(
				'name' => 'm_'.$column,
				'type' => (strpos($column, 'field_ft_') !== FALSE) ? 'tinytext' : 'text'
			);
		}

		ee()->smartforge->modify_column('member_data', $columns_to_modify);
	}

	/**
	 * Add snippet edit dates so that we know when files are stale
	 */
	private function _add_snippet_edit_date()
	{
		ee()->smartforge->add_column(
			'snippets',
			array(
				'edit_date'        => array(
					'type'         => 'int',
					'constraint'   => 10,
					'null'         => FALSE,
					'default'      => 0
				),
			)
		);

		if (ee()->config->item('save_tmpl_files') == 'y')
		{
			$snippets = ee('Model')->get('Snippet')->all();
			$snippets->save();
		}
	}
}
// EOF
