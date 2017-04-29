<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
		$steps = new ProgressIterator(
			array(
				'addRelationshipModule',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addRelationshipModule()
	{
		$installed = ee()->db->get_where('modules', array('module_name' => 'Relationship'));

		if ($installed->num_rows() > 0)
		{
			return;
		}

		ee()->db->insert(
			'modules',
			array(
				'module_name'        => 'Relationship',
				'module_version'     => '1.0.0',
				'has_cp_backend'     => 'n',
				'has_publish_fields' => 'n'
			)
		);

		ee()->db->insert_batch(
			'actions',
			array(
				array(
					'class'  => 'Relationship',
					'method' => 'entryList'
				)
			)
		);
	}
}

// EOF
